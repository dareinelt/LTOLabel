<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="page-head">
  <h1>Labels</h1>
  <a class="btn btn-primary" href="/labels/create">+ Neu erstellen</a>
</div>

<form method="get" action="/labels" class="filters">
  <label>
    Medientyp
    <select name="media_type">
      <option value="ALL" <?= $filters['media_type'] === 'ALL' ? 'selected' : '' ?>>Alle</option>
      <option value="DATA" <?= $filters['media_type'] === 'DATA' ? 'selected' : '' ?>>Datenband</option>
      <option value="CLEANING" <?= $filters['media_type'] === 'CLEANING' ? 'selected' : '' ?>>Cleaning Tape</option>
    </select>
  </label>
  <label>
    Generation
    <select name="media_generation">
      <option value="ALL" <?= $filters['media_generation'] === 'ALL' ? 'selected' : '' ?>>Alle</option>
      <?php foreach ($generations as $generation): ?>
        <option value="<?= e($generation) ?>" <?= $filters['media_generation'] === $generation ? 'selected' : '' ?>><?= e($generation) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>
    Suche
    <input type="text" name="search" value="<?= e($filters['search']) ?>" placeholder="Code…">
  </label>
  <label>
    Status
    <select name="printed">
      <option value="" <?= $filters['printed'] === '' ? 'selected' : '' ?>>Alle</option>
      <option value="1" <?= $filters['printed'] === '1' ? 'selected' : '' ?>>Gedruckt</option>
      <option value="0" <?= $filters['printed'] === '0' ? 'selected' : '' ?>>Ungedruckt</option>
    </select>
  </label>
  <button type="submit" class="btn">Filtern</button>
</form>

<?php if ($labels === []): ?>
  <p class="muted">Keine Labels gefunden.</p>
<?php else: ?>
  <form method="get" action="/print/sheet">
    <table>
      <thead>
        <tr>
          <th><input type="checkbox" id="check-all" aria-label="Alle auswählen"></th>
          <th>Code</th>
          <th>Typ</th>
          <th>Gen.</th>
          <th>Gedruckt</th>
          <th>Erstellt</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($labels as $label): ?>
        <tr>
          <td><input type="checkbox" name="ids[]" value="<?= (int) $label->id ?>"></td>
          <td class="mono"><?= e($label->labelCode) ?></td>
          <td><?= e($label->mediaType->label()) ?></td>
          <td><?= e($label->mediaGeneration) ?></td>
          <td><?= (int) $label->printCount ?>×</td>
          <td><?= e($label->createdAt ?? '') ?></td>
          <td><a class="btn btn-small" href="/print/single?id=<?= (int) $label->id ?>">Drucken</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <p class="actions">
      <button type="submit" id="print-selection" class="btn btn-primary" disabled>Auswahl drucken (Blatt)</button>
    </p>
  </form>
<?php endif; ?>

<script>
(function () {
  var boxes = document.querySelectorAll('input[name="ids[]"]');
  var button = document.getElementById('print-selection');
  var checkAll = document.getElementById('check-all');

  function updateButton() {
    var any = false;
    boxes.forEach(function (cb) { if (cb.checked) { any = true; } });
    button.disabled = !any;
  }

  boxes.forEach(function (cb) {
    cb.addEventListener('change', updateButton);
  });

  checkAll.addEventListener('change', function () {
    boxes.forEach(function (cb) { cb.checked = checkAll.checked; });
    updateButton();
  });
})();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
