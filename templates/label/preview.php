<?php require __DIR__ . '/../layout/header.php'; ?>

<h1>Vorschau</h1>

<p class="muted">
  Profil <strong><?= e($profile->name) ?></strong> &middot;
  Medientyp <strong><?= e($mediaTypeProfile->type->label()) ?></strong> &middot;
  <?= count($preview) ?> Label
</p>

<?php if ($duplicates !== []): ?>
  <div class="flash flash-warning">
    <?= count($duplicates) ?> Code(s) existieren bereits: <?= e(implode(', ', $duplicates)) ?>
  </div>
<?php endif; ?>

<div class="preview-grid">
  <?php foreach ($preview as $item): ?>
    <?php $label = $item['label']; $result = $item['result']; ?>
    <div class="preview-card <?= $result->isValid() ? '' : 'preview-invalid' ?>">
      <div class="preview-label">
        <div class="barcode"><?= $item['svg'] ?></div>
        <div class="label-code mono"><?= e($label->labelCode) ?></div>
      </div>
      <ul class="result-list">
        <?php foreach ($result->errors() as $error): ?>
          <li class="err"><?= e($error) ?></li>
        <?php endforeach; ?>
        <?php foreach ($result->warnings() as $warning): ?>
          <li class="warn"><?= e($warning) ?></li>
        <?php endforeach; ?>
        <?php if ($result->isValid() && $result->warnings() === []): ?>
          <li class="ok">OK</li>
        <?php endif; ?>
      </ul>
    </div>
  <?php endforeach; ?>
</div>

<form method="post" action="/labels/create">
  <?= $csrfField ?>
  <?php foreach ($form as $key => $value): ?>
    <input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>">
  <?php endforeach; ?>
  <p class="actions">
    <a class="btn" href="/labels/create">Zurück</a>
    <button type="submit" class="btn btn-primary">Erstellen</button>
  </p>
</form>

<?php require __DIR__ . '/../layout/footer.php'; ?>
