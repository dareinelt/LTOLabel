<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="page-head">
  <h1>Druckhistorie</h1>
  <a class="btn" href="/print/test">Testseite drucken</a>
</div>

<form method="get" action="/print/history" class="filters">
  <label>
    Medientyp
    <select name="media_type">
      <option value="ALL" <?= $filter === 'ALL' ? 'selected' : '' ?>>Alle</option>
      <option value="DATA" <?= $filter === 'DATA' ? 'selected' : '' ?>>Datenband</option>
      <option value="CLEANING" <?= $filter === 'CLEANING' ? 'selected' : '' ?>>Cleaning Tape</option>
    </select>
  </label>
  <button type="submit" class="btn">Filtern</button>
</form>

<?php if ($history === []): ?>
  <p class="muted">Noch nichts gedruckt.</p>
<?php else: ?>
  <table>
    <thead><tr><th>Code</th><th>Typ</th><th>Gen.</th><th>Anzahl</th><th>Zuletzt gedruckt</th></tr></thead>
    <tbody>
    <?php foreach ($history as $label): ?>
      <tr>
        <td class="mono"><?= e($label->labelCode) ?></td>
        <td><?= e($label->mediaType->label()) ?></td>
        <td><?= e($label->mediaGeneration) ?></td>
        <td><?= (int) $label->printCount ?>×</td>
        <td><?= e($label->printedAt ?? '') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php require __DIR__ . '/../layout/footer.php'; ?>
