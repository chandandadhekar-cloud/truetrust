<?php defined('APP_ROOT') || exit; ?>
<!doctype html>
<html><body style="margin:0;background:#FBF8F4;font-family:Helvetica,Arial,sans-serif;color:#1A1714">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FBF8F4;padding:32px 16px">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#ffffff;border-radius:20px;padding:36px 32px">
<tr><td>
<div style="font-size:22px;font-weight:bold;color:#E0435E;margin-bottom:28px"><?= e(setting('site_name')) ?></div>
<h1 style="font-size:24px;margin:0 0 14px;font-weight:bold"><?= e($heading) ?></h1>
<p style="font-size:15px;line-height:1.6;color:#4A433D;margin:0 0 26px"><?= e($intro) ?></p>
<?php if (!empty($link)): ?>
<p style="margin:0 0 26px"><a href="<?= e($link) ?>" style="display:inline-block;background:#E0435E;color:#ffffff;text-decoration:none;font-weight:bold;font-size:15px;padding:14px 26px;border-radius:999px"><?= e($button) ?></a></p>
<p style="font-size:12px;line-height:1.6;color:#8C837B;margin:0 0 20px;word-break:break-all">Or paste this link into your browser:<br><?= e($link) ?></p>
<?php endif; ?>
<p style="font-size:13px;line-height:1.6;color:#8C837B;margin:0"><?= e($outro ?? '') ?></p>
</td></tr>
</table>
<p style="font-size:12px;color:#8C837B;margin-top:18px"><?= e(setting('site_name')) ?> · Free dating for India</p>
</td></tr>
</table>
</body></html>
