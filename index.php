<?php
// Connexion
require __DIR__ . '/database.php';

// Helpers
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Libellés UI ↔︎ valeurs BDD
const UI2DB_STATUS = ['A faire'=>'à faire','En cours'=>'en cours','Terminé'=>'terminée'];
const DB2UI_STATUS = ['à faire'=>'A faire','en cours'=>'En cours','terminée'=>'Terminé'];
const DB2UI_PRIORITY = ['haute'=>'Haute','moyenne'=>'Moyenne','basse'=>'Basse'];

// Colonnes et ordre d’affichage
$statuses = ['A faire'=>'a-faire-column','En cours'=>'en-cours-column','Terminé'=>'termine-column'];
$priorities = ['Haute','Moyenne','Basse'];

/** Récupère les tâches par statut (libellé UI) triées priorité → due_date → id DESC */
function getTasksByStatus(PDO $pdo, string $uiStatus): array {
  $dbStatus = UI2DB_STATUS[$uiStatus] ?? $uiStatus;
  $sql = "SELECT id,title,description,status,priority,due_date,created_at,updated_at
          FROM tasks
          WHERE status = :status
          ORDER BY FIELD(priority,'haute','moyenne','basse'),
                   due_date IS NULL, due_date,
                   id DESC";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':status'=>$dbStatus]);
  return $stmt->fetchAll() ?: [];
}

function displayTask(array $t): void {
  $completed = ($t['status'] === 'terminée');
  $checked   = $completed ? 'checked' : '';
  $class     = $completed ? 'completed' : '';
  $uiPrio    = DB2UI_PRIORITY[$t['priority']] ?? $t['priority'];

  echo "<div class='task-item $class priority-{$uiPrio}' data-id='".(int)$t['id']."'>";
  echo "<input type='checkbox' class='task-checkbox' $checked onchange='toggleTask(".(int)$t["id"].")'>";
  if (!empty($t['title'])) echo "<strong class='task-title'>".e($t['title'])."</strong> - ";
  echo "<span class='task-description'>".e($t['description'] ?? '')."</span>";
  if (!empty($t['due_date'])) echo " <span class='task-due' title='Échéance'>(".e($t['due_date']).")</span>";
  if (!empty($t['created_at'])) {
    $ts = strtotime($t['created_at']); $txt = $ts ? date('d/m/Y H:i',$ts) : e($t['created_at']);
    echo "<span class='task-date'>$txt</span>";
  }
  echo " <a class='delete-link' href='traitement.php?action=delete&id=".(int)$t['id']."' onclick=\"return confirm('Supprimer ?')\">🗑️</a>";
  echo "</div>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Task CRUD</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<div class="container">
  <header>
    <h1>Task CRUD</h1>
    <nav>
      <button onclick="clearCompleted()">Supprimer les terminées</button>
      <button onclick="location.reload()">Rafraîchir</button>
    </nav>
  </header>

  <div class="add-task-form">
    <h2>Ajouter une tâche</h2>
    <form action="traitement.php" method="POST">
      <input type="hidden" name="action" value="add">
      <input type="text" name="task_title" placeholder="Titre (optionnel)">
      <input type="text" name="task_description" placeholder="Description..." required>

      <select name="status" required>
        <option value="">État</option>
        <?php foreach ($statuses as $label => $class): ?>
          <option value="<?= e($label) ?>"><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>

      <select name="priority" required>
        <option value="">Priorité</option>
        <?php foreach ($priorities as $p): ?>
          <option value="<?= e($p) ?>"><?= e($p) ?></option>
        <?php endforeach; ?>
      </select>

      <input type="date" name="due_date" placeholder="Échéance (optionnel)">
      <button type="submit">Ajouter</button>
    </form>
  </div>

  <div class="columns-container">
    <?php foreach ($statuses as $statusKey => $cssClass): ?>
      <div class="column <?= e($cssClass) ?>">
        <h3><?= e($statusKey) ?></h3>
        <div class="tasks-list">
          <?php
            $tasks = getTasksByStatus($pdo, $statusKey);
            // Affichage par priorité dans l’ordre souhaité
            foreach (['Haute','Moyenne','Basse'] as $prioWanted) {
              foreach ($tasks as $t) {
                $uiPrio = DB2UI_PRIORITY[$t['priority']] ?? $t['priority'];
                if ($uiPrio === $prioWanted) displayTask($t);
              }
            }
          ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
// Bascule terminé/à faire
function toggleTask(id){
  fetch('traitement.php?action=toggle_done&id='+encodeURIComponent(id), {method:'POST'})
    .then(()=>location.reload());
}
// Supprimer toutes les terminées
function clearCompleted(){
  if(confirm('Supprimer toutes les tâches terminées ?')){
    fetch('traitement.php?action=clear_completed', {method:'POST'})
      .then(()=>location.reload());
  }
}
</script>
<script src="assets/script.js"></script>
</body>
</html>
