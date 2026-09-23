<?php require __DIR__ . '/layout/header.php'; ?>

<h1>Dashboard</h1>

<div class="cards">
  <div class="card">
    <div class="card-value"><?= (int) $count ?></div>
    <div class="card-label">Labels gesamt</div>
  </div>
  <div class="card">
    <div class="card-value"><?= count($history) ?></div>
    <div class="card-label">zuletzt gedruckt</div>
  </div>
  <div class="card">
    <div class="card-value"><?= count($profiles) ?></div>
    <div class="card-label">Profile</div>
  </div>
</div>

<div class="grid-2">
  <section>
    <h2>Profile</h2>
    <table>
      <thead><tr><th>Name</th><th>Label (mm)</th><th>Barcode</th></tr></thead>
      <tbody>
      <?php foreach ($profiles as $name => $profile): ?>
        <tr>
          <td><?= e($name) ?><?= $name === $defaultProfileName ? ' <span class="badge">Standard</span>' : '' ?></td>
          <td><?= e(sprintf('%.1f × %.1f', $profile->labelWidthMm, $profile->labelHeightMm)) ?></td>
          <td><?= e($profile->barcode->type) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <section>
    <h2>Zuletzt erstellt</h2>
    <?php if ($recent === []): ?>
      <p class="muted">Noch keine Labels vorhanden.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Code</th><th>Typ</th><th>Erstellt</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $label): ?>
          <tr>
            <td><a href="/print/single?id=<?= (int) $label->id ?>"><?= e($label->labelCode) ?></a></td>
            <td><?= e($label->mediaType->label()) ?></td>
            <td><?= e($label->createdAt ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
</div>

<section>
  <h2>Druckhistorie</h2>
  <?php if ($history === []): ?>
    <p class="muted">Noch nichts gedruckt.</p>
  <?php else: ?>
    <table>
      <thead><tr><th>Code</th><th>Typ</th><th>Anzahl</th><th>Zuletzt</th></tr></thead>
      <tbody>
      <?php foreach ($history as $label): ?>
        <tr>
          <td><?= e($label->labelCode) ?></td>
          <td><?= e($label->mediaType->label()) ?></td>
          <td><?= (int) $label->printCount ?></td>
          <td><?= e($label->printedAt ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/layout/footer.php'; ?>
