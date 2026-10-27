<?php defined('APP_ROOT') || exit; ?>
<div class="photo-grid" data-photo-grid data-max="<?= (int) $max ?>">
    <?php for ($i = 0; $i < $max; $i++): $p = $photos[$i] ?? null; ?>
        <?php if ($p): ?>
            <div class="photo-slot has-photo" data-photo-id="<?= (int) $p['id'] ?>">
                <img src="<?= photo_url((int) $p['id'], 'thumb') ?>" alt="">
                <div class="ph-actions">
                    <?php if ($i > 0): ?><button type="button" class="ph-btn" data-photo-first title="Make cover photo"><?= icon('star', 16) ?></button><?php endif; ?>
                    <button type="button" class="ph-btn" data-photo-delete title="Remove photo"><?= icon('trash', 16) ?></button>
                </div>
                <?php if ($p['status'] === 'pending'): ?><span class="ph-tag pending">In review</span><?php elseif ($i === 0): ?><span class="ph-tag">Cover</span><?php endif; ?>
            </div>
        <?php else: ?>
            <label class="photo-slot">
                <span class="add"><?= icon('plus', 22) ?></span>
                <input type="file" accept="image/jpeg,image/png,image/webp" data-photo-input aria-label="Add photo">
            </label>
        <?php endif; ?>
    <?php endfor; ?>
</div>
