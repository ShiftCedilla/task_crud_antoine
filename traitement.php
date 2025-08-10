<?php
require __DIR__ . '/database.php';

// UI → DB
const UI2DB_STATUS   = ['A faire'=>'à faire','En cours'=>'en cours','Terminé'=>'terminée'];
const UI2DB_PRIORITY = ['Haute'=>'haute','Moyenne'=>'moyenne','Basse'=>'basse'];

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// AJOUT
if ($action === 'add') {
  $title = trim($_POST['task_title'] ?? '');
  $desc  = trim($_POST['task_description'] ?? '');
  $stUI  = $_POST['status']   ?? 'A faire';
  $prUI  = $_POST['priority'] ?? 'Moyenne';
  $due   = $_POST['due_date'] ?? null; if ($due === '') $due = null;

  $sql = "INSERT INTO tasks (title,description,status,priority,due_date)
          VALUES (:title,:description,:status,:priority,:due_date)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':title'       => $title !== '' ? $title : null,
    ':description' => $desc  !== '' ? $desc  : null,
    ':status'      => UI2DB_STATUS[$stUI] ?? 'à faire',
    ':priority'    => UI2DB_PRIORITY[$prUI] ?? 'moyenne',
    ':due_date'    => $due
  ]);
  header('Location: index.php'); exit;
}

// TOGGLE terminé / à faire
if ($action === 'toggle_done') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id > 0) {
    $cur = $pdo->prepare("SELECT status FROM tasks WHERE id=?");
    $cur->execute([$id]);
    $s = $cur->fetchColumn();
    if ($s !== false) {
      $next = ($s === 'terminée') ? 'à faire' : 'terminée';
      $pdo->prepare("UPDATE tasks SET status=? WHERE id=?")->execute([$next,$id]);
    }
  }
  http_response_code(204); exit;
}

// CLEAR terminées
if ($action === 'clear_completed') {
  $pdo->exec("DELETE FROM tasks WHERE status='terminée'");
  http_response_code(204); exit;
}

// SUPPRIMER une tâche
if ($action === 'delete') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id > 0) $pdo->prepare("DELETE FROM tasks WHERE id=?")->execute([$id]);
  header('Location: index.php'); exit;
}

http_response_code(400);
echo 'Action inconnue';
