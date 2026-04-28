<?php
/**
 * @var object $image Объект изображения
 */
if (!$image) return;

$sizes_repo = new BM\Repositories\ImageRepository();
$all_sizes = $sizes_repo->getAllSizes($image->img_group_id);
?>

<div class="bm-image-card">
    <div class="bm-image-card__preview">
        <img 
            src="<?= esc_url($image->url) ?>" 
            alt="<?= esc_attr($image->alt) ?>"
            width="<?= $image->width ?>"
            height="<?= $image->height ?>"
        >
    </div>
    
    <div class="bm-image-card__info">
        <div class="bm-image-card__meta">
            <span class="bm-image-card__size">
                <?= $image->width ?>x<?= $image->height ?> px
            </span>
            <span class="bm-image-card__format">
                <?= strtoupper($image->ext) ?>
            </span>
        </div>
        
        <?php if ($all_sizes && count($all_sizes) > 1): ?>
        <div class="bm-image-card__sizes">
            <h4>Другие размеры:</h4>
            <ul>
                <?php foreach ($all_sizes as $size): ?>
                    <?php if ($size->id != $image->id): ?>
                    <li>
                        <a href="<?= esc_url($size->url ?? '#') ?>">
                            <?= $size->width ?>x<?= $size->height ?>
                        </a>
                    </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if ($image->theme): ?>
        <div class="bm-image-card__theme">
            Тема: <?= esc_html($image->theme->name) ?>
        </div>
        <?php endif; ?>
    </div>
</div>