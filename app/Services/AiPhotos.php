<?php
namespace App\Services;

defined('APP_ROOT') || exit;

use App\Core\Config;
use App\Core\DB;
use App\Core\Image;
use App\Core\Settings;
use App\Models\Photo;

/**
 * AI photo generation for seed profiles (OpenAI Images API).
 *
 * Shot 0 creates the person from a written description. Every later shot is
 * an image edit that uses shot 0 as the face reference, so all photos of one
 * profile show the same person. Photos are stored with photos.is_ai = 1 and
 * members see an "AI-generated photos" label on those profiles.
 *
 * Jobs run one image per request (see Admin\SeedController::runAi), so a slow
 * API call never hits the web server's timeout, and spending is capped by the
 * ai_budget_usd setting.
 */
final class AiPhotos
{
    /** Tried in this order when the selected model isn't available to the key. */
    public const PREFERRED = ['gpt-image-1.5', 'gpt-image-1', 'gpt-image-2', 'gpt-image-1-mini'];
    public const QUALITIES = ['low', 'medium', 'high'];
    public const MAX_SHOTS = 4;

    // USD per 1M tokens: [text input, image input, image output].
    // Models not listed here are costed at gpt-image-1 rates (a conservative estimate).
    private const RATES = [
        'gpt-image-1'      => [5.0, 10.0, 40.0],
        'gpt-image-1-mini' => [2.0, 2.5, 8.0],
    ];
    // Fallback per-image estimates for a 1024x1536 portrait when usage is not reported.
    private const ESTIMATE = [
        'gpt-image-1'      => ['low' => 0.016, 'medium' => 0.063, 'high' => 0.25],
        'gpt-image-1-mini' => ['low' => 0.006, 'medium' => 0.015, 'high' => 0.052],
    ];

    public static function configured(): bool
    {
        return trim((string) Settings::get('openai_api_key', '')) !== '';
    }

    public static function model(): string
    {
        $m = trim((string) Settings::get('openai_image_model', 'gpt-image-1.5'));
        return preg_match('/^[a-z0-9][a-z0-9.\-]{2,60}$/', $m) ? $m : 'gpt-image-1.5';
    }

    /** Image models offered in Settings: the ones found on the key by Check connection, else defaults. */
    public static function models(): array
    {
        $found = array_filter(explode(',', (string) Settings::get('ai_models_available', '')));
        $list = $found ?: ['gpt-image-1.5', 'gpt-image-1-mini'];
        if (!in_array(self::model(), $list, true)) {
            $list[] = self::model();
        }
        return array_values(array_unique($list));
    }

    /** Only gpt-image-1 is known to accept input_fidelity; other models get the plain edit. */
    private static function supportsFidelity(string $model): bool
    {
        return $model === 'gpt-image-1';
    }

    /** Estimated cost of one image, including the face reference for later shots. */
    public static function estimate(string $quality, bool $withReference = false): float
    {
        $base = (self::ESTIMATE[self::model()] ?? self::ESTIMATE['gpt-image-1'])[$quality] ?? 0.063;
        return round($base + ($withReference ? $base * 0.25 : 0), 4);
    }

    public static function spent(): float
    {
        return (float) Settings::get('ai_spent_usd', '0');
    }

    public static function budget(): float
    {
        return (float) Settings::get('ai_budget_usd', '3');
    }

    public static function counts(): array
    {
        $rows = DB::all('SELECT status, COUNT(*) n FROM ai_photo_jobs GROUP BY status');
        $out = ['queued' => 0, 'running' => 0, 'done' => 0, 'failed' => 0];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['n'];
        }
        return $out;
    }

    /**
     * Queues photos for seed profiles. Returns the number of profiles queued.
     * Only seed profiles are eligible; profiles with jobs already waiting are skipped.
     */
    public static function queue(array $userIds, int $shots, string $quality): int
    {
        $shots = max(1, min(self::MAX_SHOTS, $shots));
        $quality = in_array($quality, self::QUALITIES, true) ? $quality : 'medium';
        $n = 0;
        foreach (array_unique(array_map('intval', $userIds)) as $id) {
            $u = DB::one("SELECT * FROM users WHERE id = ? AND is_seed = 1 AND status <> 'deleted'", [$id]);
            if (!$u || DB::value("SELECT 1 FROM ai_photo_jobs WHERE user_id = ? AND status IN ('queued','running')", [$id])) {
                continue;
            }
            // Continue numbering after any AI photos the profile already has.
            $start = (int) DB::value("SELECT COALESCE(MAX(shot) + 1, 0) FROM ai_photo_jobs WHERE user_id = ? AND status = 'done'", [$id]);
            $person = self::person($u);
            for ($s = $start; $s < $start + $shots; $s++) {
                DB::insert('ai_photo_jobs', [
                    'user_id'    => $id,
                    'shot'       => $s,
                    'person'     => $person,
                    'prompt'     => self::shotPrompt($u, $person, $s),
                    'quality'    => $quality,
                    'created_at' => now(),
                ]);
            }
            $n++;
        }
        return $n;
    }

    /**
     * Generates the next image in the queue.
     * @return array{state:string, message:string, counts:array, photo?:array}
     */
    public static function runNext(): array
    {
        if (!self::configured()) {
            return self::result('stopped', 'Add your OpenAI API key in Settings first.');
        }
        if ((int) DB::value("SELECT GET_LOCK('tt_ai_photos', 0)") !== 1) {
            return self::result('busy', 'Another generation is in progress.');
        }
        try {
            // A job stuck in "running" means its request died; don't silently pay twice.
            DB::query("UPDATE ai_photo_jobs SET status = 'failed', error = 'Interrupted. Retry to try again.', finished_at = NOW()
                       WHERE status = 'running' AND started_at < NOW() - INTERVAL 6 MINUTE");

            $job = self::nextJob();
            if (!$job) {
                return self::result('idle', 'Queue is empty.');
            }
            $reference = null;
            if ((int) $job['shot'] > 0) {
                $reference = self::referenceImage((int) $job['user_id']);
                if ($reference === null) {
                    self::fail((int) $job['id'], 'The first photo of this person failed, so there is no face to match.');
                    return self::result('failed', 'Skipped a photo: its reference portrait is missing.');
                }
            }
            $cost = self::estimate($job['quality'], $reference !== null);
            if (self::spent() + $cost > self::budget()) {
                return self::result('stopped', sprintf('Budget reached ($%.2f of $%.2f). Raise the budget in Settings to continue.', self::spent(), self::budget()));
            }

            DB::query("UPDATE ai_photo_jobs SET status = 'running', started_at = NOW() WHERE id = ?", [$job['id']]);
            try {
                $res = $reference === null
                    ? self::client('images/generations', self::generationBody($job), false)
                    : self::client('images/edits', self::editBody($job, $reference), true);
            } catch (\RuntimeException $e) {
                $code = $e->getCode();
                // Account-level problems: put the job back and stop the run.
                if (in_array($code, [401, 403, 429], true) || $code >= 500 || $code === 0) {
                    DB::query("UPDATE ai_photo_jobs SET status = 'queued', started_at = NULL WHERE id = ?", [$job['id']]);
                    return self::result('stopped', $e->getMessage());
                }
                self::fail((int) $job['id'], $e->getMessage());
                return self::result('failed', 'One photo failed: ' . $e->getMessage());
            }

            // The API has charged us at this point, so record the spend first.
            $cost = self::costFromUsage($res['usage'] ?? null) ?? $cost;
            Settings::set('ai_spent_usd', (string) round(self::spent() + $cost, 4));
            DB::query('UPDATE ai_photo_jobs SET cost_usd = ? WHERE id = ?', [$cost, $job['id']]);

            $tmp = tempnam(sys_get_temp_dir(), 'ttai');
            file_put_contents($tmp, $res['bytes']);
            try {
                $img = Image::fromPath($tmp, strlen($res['bytes']));
            } catch (\RuntimeException $e) {
                self::fail((int) $job['id'], 'Could not process the returned image: ' . $e->getMessage());
                return self::result('failed', 'One photo could not be processed.');
            } finally {
                @unlink($tmp);
            }
            $photoId = Photo::storeProcessed((int) $job['user_id'], $img, true, true);
            DB::query(
                "UPDATE ai_photo_jobs SET status = 'done', photo_id = ?, error = '', finished_at = NOW() WHERE id = ?",
                [$photoId, $job['id']]
            );

            $name = (string) DB::value('SELECT first_name FROM users WHERE id = ?', [$job['user_id']]);
            return self::result('done', "Created photo " . ((int) $job['shot'] + 1) . " for {$name}.", [
                'id' => $photoId, 'url' => photo_url($photoId, 'thumb'), 'user' => (int) $job['user_id'], 'name' => $name,
            ]);
        } finally {
            DB::value("SELECT RELEASE_LOCK('tt_ai_photos')");
        }
    }

    public static function retryFailed(): int
    {
        return DB::query("UPDATE ai_photo_jobs SET status = 'queued', error = '', started_at = NULL, finished_at = NULL WHERE status = 'failed'")->rowCount();
    }

    public static function clearQueue(): int
    {
        return DB::query("DELETE FROM ai_photo_jobs WHERE status IN ('queued','failed')")->rowCount();
    }

    private static function result(string $state, string $message, ?array $photo = null): array
    {
        $out = ['state' => $state, 'message' => $message, 'counts' => self::counts(), 'spent' => self::spent(), 'budget' => self::budget()];
        if ($photo) {
            $out['photo'] = $photo;
        }
        return $out;
    }

    private static function fail(int $jobId, string $error): void
    {
        DB::query("UPDATE ai_photo_jobs SET status = 'failed', error = ?, finished_at = NOW() WHERE id = ?", [mb_substr($error, 0, 500), $jobId]);
    }

    /** Next runnable job: first shots first, later shots only once their reference exists. */
    private static function nextJob(): ?array
    {
        return DB::one(
            "SELECT j.* FROM ai_photo_jobs j
             WHERE j.status = 'queued'
               AND (j.shot = 0
                    OR EXISTS (SELECT 1 FROM ai_photo_jobs r WHERE r.user_id = j.user_id AND r.shot = 0 AND r.status IN ('done','failed'))
                    OR NOT EXISTS (SELECT 1 FROM ai_photo_jobs r WHERE r.user_id = j.user_id AND r.shot = 0))
             ORDER BY j.shot = 0 DESC, j.id
             LIMIT 1"
        );
    }

    /** The profile's first AI portrait, used as the face reference. */
    private static function referenceImage(int $userId): ?string
    {
        $bytes = DB::value(
            "SELECT b.data FROM ai_photo_jobs j JOIN photo_blobs b ON b.photo_id = j.photo_id
             WHERE j.user_id = ? AND j.status = 'done' ORDER BY j.shot LIMIT 1",
            [$userId]
        );
        return $bytes === null ? null : (string) $bytes;
    }

    /* ---------------- Prompts ---------------- */

    /** A stable written description of one person, reused for every shot. */
    public static function person(array $u): string
    {
        mt_srand(crc32('tt-person-' . $u['id']));
        $man = $u['gender'] === 'man';
        $age = age($u['dob']) ?: 26;
        $build = self::pick(['slim', 'lean', 'average', 'average', 'athletic', 'slightly heavy']);
        $complexion = self::pick(['light brown', 'wheatish', 'medium brown', 'medium brown', 'deep brown']);
        $face = self::pick(['oval face', 'round face', 'square jaw', 'soft features', 'high cheekbones', 'long face']);
        if ($man) {
            $hair = self::pick(['short black hair, neatly trimmed', 'short black hair, slightly messy', 'medium-length wavy black hair', 'black hair with a side part', 'closely cropped black hair', 'thick black hair, swept back']);
            $extra = self::pick(['clean-shaven', 'light stubble', 'light stubble', 'short trimmed beard', 'full but neat beard', 'thin moustache and stubble']);
        } else {
            $hair = self::pick(['long straight black hair, worn open', 'long black hair in a loose braid', 'shoulder-length wavy dark brown hair', 'long black hair tied in a low ponytail', 'shoulder-length straight black hair', 'curly black hair, medium length']);
            $extra = self::pick(['small stud earrings', 'no makeup apart from kajal', 'light everyday makeup', 'a small bindi', 'thin gold chain', 'no jewellery']);
        }
        $glasses = mt_rand(1, 100) <= 18 ? ', wears thin-framed glasses' : '';
        mt_srand();
        return sprintf(
            '%d-year-old Indian %s from %s, %s build, %s skin, %s, %s, %s%s',
            $age, $man ? 'man' : 'woman', $u['city'] ?: 'India', $build, $complexion, $face, $hair, $extra, $glasses
        );
    }

    /** Scene for each shot, chosen from the profile's interests so photos fit the bio. */
    public static function shotPrompt(array $u, string $person, int $shot): string
    {
        mt_srand(crc32('tt-shot-' . $u['id'] . '-' . $shot));
        $man = $u['gender'] === 'man';
        $interests = array_filter(array_map('trim', explode(',', (string) $u['interests'])));
        $scenes = [
            'Travel' => 'standing at a hill viewpoint on a trip, mountains behind',
            'Mountains' => 'on a mountain trail, wearing a light jacket',
            'Trekking' => 'resting on a rock during a trek, backpack straps visible',
            'Road trips' => 'leaning against a car at a highway dhaba',
            'Beaches' => 'on a beach in the late afternoon, sea behind',
            'Chai' => 'holding a small glass of cutting chai at a roadside tea stall',
            'Coffee' => 'sitting at a cafe table with a cup of coffee',
            'Cricket' => 'at a local cricket ground holding a bat',
            'Reading' => 'browsing in a small bookstore, holding a book',
            'Cooking' => 'cooking in a home kitchen',
            'Baking' => 'in a home kitchen with a tray of freshly baked cookies',
            'Gym' => 'at a gym in casual workout clothes',
            'Running' => 'in a park after a morning run, in running clothes',
            'Guitar' => 'sitting at home holding an acoustic guitar',
            'Dogs' => 'in a park crouching next to a friendly dog',
            'Photography' => 'holding a camera on a busy street',
            'Street food' => 'at a street food stall holding a plate of pani puri',
            'Art' => 'at an art gallery looking towards the camera',
            'Yoga' => 'sitting on a yoga mat on a terrace in the morning',
            'Concerts' => 'at an outdoor music event in the evening',
        ];
        $hobby = null;
        foreach ($interests as $i) {
            if (isset($scenes[$i])) {
                $hobby = $scenes[$i];
                break;
            }
        }
        $hobby = $hobby ?? 'walking on a tree-lined street in ' . ($u['city'] ?: 'the city');

        $casual = $man
            ? self::pick(['a plain navy t-shirt', 'a casual checked shirt with sleeves rolled up', 'a white linen shirt', 'a grey polo t-shirt', 'a black crew-neck t-shirt', 'a denim shirt'])
            : self::pick(['a simple cotton kurti', 'a plain white top', 'a pastel blouse', 'a casual striped t-shirt', 'a denim jacket over a plain top', 'a printed cotton dress']);
        $festive = $man
            ? self::pick(['a light cotton kurta', 'a maroon kurta with white pyjama', 'a cream-coloured kurta'])
            : self::pick(['a cotton saree', 'a festive anarkali suit', 'a silk kurta with dupatta']);
        $place = self::pick(['on a home balcony with potted plants', 'at a cafe by the window', 'in a park', 'on a quiet residential street', 'in a living room near a window']);
        mt_srand();

        $shots = [
            "Casual photo, head and shoulders, of a {$person}. Taken by a friend on a phone {$place}, soft natural daylight, relaxed genuine smile, looking at the camera, wearing {$casual}.",
            "Candid photo of the same {$person}, {$hobby}, half-body framing, natural light, wearing casual clothes, looking slightly off camera.",
            "Full-length photo of the same {$person} at a family function in the evening, wearing {$festive}, warm indoor lights and decorations behind, standing and smiling.",
            "Selfie taken at arm's length with a phone front camera by the same {$person}, indoors in daylight, natural expression, wearing {$casual}.",
        ];
        $base = $shots[$shot % count($shots)];
        $style = ' Photorealistic, ordinary everyday look, realistic skin texture and lighting, like an unedited phone photo. Only this one adult person in the frame, fully clothed, no text, no watermark.';
        if ($shot > 0) {
            return 'Use the person in the reference photo. Keep exactly the same face, facial features, skin tone, hairline and age. ' . $base . $style;
        }
        return $base . $style;
    }

    private static function pick(array $list)
    {
        return $list[mt_rand(0, count($list) - 1)];
    }

    /**
     * Free connection check: lists the key's image models, then confirms the
     * selected one is usable by sending a request with an invalid size (the API
     * checks model access first and rejects the size without generating
     * anything). If the selected model isn't usable, the first working
     * preferred model is selected instead.
     * @return array{ok:bool, message:string, models:array, model:string}
     */
    public static function checkConnection(): array
    {
        if (!self::configured()) {
            return ['ok' => false, 'message' => 'Save an API key first.', 'models' => [], 'model' => self::model()];
        }
        try {
            $list = self::client('models', null, false)['json']['data'] ?? [];
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'models' => [], 'model' => self::model()];
        }
        $listed = array_values(array_filter(array_column($list, 'id'), static fn ($id) => strpos($id, 'image') !== false && strpos($id, 'chatgpt') !== 0));
        if (!$listed) {
            Settings::set('ai_models_available', '');
            return ['ok' => false, 'message' => 'This key has no image models. In the OpenAI dashboard, allow an image model (for example gpt-image-1.5) for the key\'s project.', 'models' => [], 'model' => self::model()];
        }

        // The listing includes models a project may block, so test each one (free).
        $usable = [];
        $problems = [];
        foreach ($listed as $m) {
            $why = self::probe($m);
            if ($why === null) {
                $usable[] = $m;
            } else {
                $problems[$m] = str_replace(' Open Settings → AI photos and press Check connection.', '', $why);
            }
        }
        sort($usable);
        Settings::set('ai_models_available', implode(',', $usable));
        if (!$usable) {
            return ['ok' => false, 'message' => 'The key works but none of its image models are allowed: ' . reset($problems), 'models' => [], 'model' => self::model()];
        }

        $current = self::model();
        $pick = in_array($current, $usable, true) ? $current : (array_values(array_intersect(self::PREFERRED, $usable))[0] ?? $usable[0]);
        Settings::set('openai_image_model', $pick);
        $note = $pick === $current
            ? "Connected. {$pick} is ready to use."
            : "Connected. Switched to {$pick}: {$current} isn't allowed for this key.";
        return ['ok' => true, 'message' => $note . ' Usable models: ' . implode(', ', $usable) . '.', 'models' => $usable, 'model' => $pick];
    }

    /** Returns null when the model is usable, otherwise the reason. Never generates an image. */
    private static function probe(string $model): ?string
    {
        try {
            self::client('images/generations', ['model' => $model, 'prompt' => 'connection check', 'size' => '8x8'], false);
            return null;
        } catch (\RuntimeException $e) {
            // An "invalid size" rejection means the model itself is accessible.
            if ($e->getCode() === 400 && stripos($e->getMessage(), 'size') !== false) {
                return null;
            }
            return $e->getMessage();
        }
    }

    /* ---------------- OpenAI client ---------------- */

    private static function generationBody(array $job): array
    {
        return [
            'model'         => self::model(),
            'prompt'        => $job['prompt'],
            'size'          => '1024x1536',
            'quality'       => $job['quality'],
            'n'             => 1,
            'output_format' => 'jpeg',
        ];
    }

    private static function editBody(array $job, string $reference): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ttref') . '.jpg';
        file_put_contents($tmp, $reference);
        register_shutdown_function(static fn () => @unlink($tmp));
        $body = [
            'model'         => self::model(),
            'prompt'        => $job['prompt'],
            'size'          => '1024x1536',
            'quality'       => $job['quality'],
            'output_format' => 'jpeg',
            'image[]'       => new \CURLFile($tmp, 'image/jpeg', 'reference.jpg'),
        ];
        if (self::supportsFidelity(self::model())) {
            $body['input_fidelity'] = 'high';
        }
        return $body;
    }

    /** @return array{bytes:string, usage:?array} */
    private static function client(string $path, ?array $body, bool $multipart): array
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('The PHP cURL extension is required for AI photos.', 0);
        }
        @set_time_limit(240);
        $base = rtrim((string) Config::get('openai.base_url', 'https://api.openai.com/v1'), '/');
        $ch = curl_init($base . '/' . $path);
        $headers = ['Authorization: Bearer ' . trim((string) Settings::get('openai_api_key'))];
        if (!$multipart) {
            $headers[] = 'Content-Type: application/json';
        }
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $multipart ? $body : json_encode($body));
        }
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 200,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new \RuntimeException('Could not reach OpenAI: ' . $err, 0);
        }
        $data = json_decode((string) $raw, true);
        if ($status < 200 || $status >= 300) {
            $msg = $data['error']['message'] ?? ('OpenAI returned HTTP ' . $status);
            $code = $data['error']['code'] ?? '';
            $model = is_array($body) ? ($body['model'] ?? '') : '';
            if ($status === 401) {
                $msg = 'OpenAI rejected the API key. Check it in Settings.';
            } elseif ($code === 'model_not_found' || $status === 404 || stripos($msg, 'does not exist') !== false
                || stripos($msg, 'must be verified') !== false || stripos($msg, 'organization') !== false && $status === 403) {
                $msg = "Your OpenAI key can't use the model {$model}: " . $msg . ' Open Settings → AI photos and press Check connection.';
                $status = 403;
            } elseif ($code === 'insufficient_quota' || $code === 'billing_hard_limit_reached') {
                $msg = 'Your OpenAI account is out of credit.';
                $status = 429;
            } elseif ($status === 429) {
                $msg = 'OpenAI rate limit reached. Wait a minute and press Start again.';
            }
            throw new \RuntimeException($msg, $status);
        }
        if ($body === null) {
            return ['bytes' => '', 'usage' => null, 'json' => $data];
        }
        $b64 = $data['data'][0]['b64_json'] ?? null;
        $bytes = $b64 ? base64_decode($b64, true) : false;
        if (!$bytes) {
            throw new \RuntimeException('OpenAI returned no image.', 422);
        }
        return ['bytes' => $bytes, 'usage' => $data['usage'] ?? null];
    }

    /** Actual cost from the API's token usage, when reported. */
    private static function costFromUsage(?array $usage): ?float
    {
        if (!$usage || !isset($usage['output_tokens'])) {
            return null;
        }
        [$textIn, $imageIn, $out] = self::RATES[self::model()] ?? self::RATES['gpt-image-1'];
        $details = $usage['input_tokens_details'] ?? [];
        $text = (int) ($details['text_tokens'] ?? ($usage['input_tokens'] ?? 0));
        $image = (int) ($details['image_tokens'] ?? 0);
        return round(($text * $textIn + $image * $imageIn + (int) $usage['output_tokens'] * $out) / 1e6, 4);
    }
}
