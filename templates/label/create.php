<?php require __DIR__ . '/../layout/header.php'; ?>

<?php
$dataProfile = $profiles[$profileName]->mediaType(\App\Label\MediaType::DATA);
$cleaningProfile = $profiles[$profileName]->mediaType(\App\Label\MediaType::CLEANING);
$dataMediaIds = $dataProfile ? $dataProfile->mediaIds : [];
$defaultMediaId = $dataProfile ? ($dataProfile->defaultMediaId ?? 'L8') : 'L8';
?>

<h1>Labels erstellen</h1>

<form method="post" action="/labels/preview" class="form">
  <?= $csrfField ?>

  <label>
    Profil
    <select name="profile">
      <?php foreach ($profilesArray as $name): ?>
        <option value="<?= e($name) ?>" <?= $name === $profileName ? 'selected' : '' ?>><?= e($name) ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <fieldset>
    <legend>Medientyp</legend>
    <label class="radio">
      <input type="radio" name="media_type" value="DATA" checked onchange="toggleMediaType()"> Datenband
    </label>
    <label class="radio">
      <input type="radio" name="media_type" value="CLEANING" onchange="toggleMediaType()"> Cleaning Tape
    </label>
  </fieldset>

  <div id="data-fields">
    <label>
      Präfix (VOLSER)
      <input type="text" name="prefix" value="" maxlength="6" placeholder="z.B. ARCHIVE">
    </label>
    <label>
      Medienkennung (Media-Identifier)
      <select name="media_id">
        <?php foreach ($dataMediaIds as $mediaId): ?>
          <option value="<?= e($mediaId) ?>" <?= $mediaId === $defaultMediaId ? 'selected' : '' ?>><?= e($mediaId) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>

  <div class="grid-2">
    <label>
      Startnummer
      <input type="number" name="start" value="1" min="1">
    </label>
    <label>
      Anzahl
      <input type="number" name="count" value="1" min="1" max="1000">
    </label>
  </div>

  <label>
    Notizen
    <input type="text" name="notes" value="">
  </label>
  <label>
    Standort
    <input type="text" name="location" value="">
  </label>

  <label>
    Bei Duplikaten
    <select name="duplicate_action">
      <option value="skip">Überspringen</option>
      <option value="replace">Ersetzen</option>
    </select>
  </label>

  <p class="hint">
    Datenband: 6 Zeichen VOLSER + Medienkennung (z.B. <span class="mono">ARCHIVEL8</span>).
    Cleaning: <span class="mono"><?= e($cleaningProfile ? $cleaningProfile->prefix : 'CLN') ?>###<?= e($cleaningProfile && $cleaningProfile->suffix !== null ? $cleaningProfile->suffix : 'L1') ?></span>.
  </p>

  <p class="actions">
    <button type="submit" class="btn">Vorschau</button>
    <button type="submit" class="btn btn-primary" formaction="/labels/create">Erstellen</button>
  </p>
</form>

<script>
function toggleMediaType() {
  var cleaning = document.querySelector('input[name="media_type"][value="CLEANING"]').checked;
  document.getElementById('data-fields').style.display = cleaning ? 'none' : 'block';
}
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
