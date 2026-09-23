<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($appName) ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="container nav-row">
    <a class="brand" href="/"><?= e($appName) ?></a>
    <nav>
      <a href="/">Dashboard</a>
      <a href="/labels">Labels</a>
      <a href="/labels/create">Neu erstellen</a>
      <a href="/print/history">Druckhistorie</a>
      <a href="/print/test">Testseite</a>
    </nav>
  </div>
</header>
<main class="container">
<?php if ($flash !== null): ?>
  <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>
