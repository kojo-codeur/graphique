<?php
session_start();

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'graphiqueDB');
define('DB_USER', 'root');
define('DB_PASS', '');

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion: " . $e->getMessage());
}

// Création de la table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS graphique (
        id INT AUTO_INCREMENT PRIMARY KEY,
        categorie VARCHAR(50) NOT NULL,
        valeur INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Insertion de données d'exemple si vide
if ($pdo->query("SELECT COUNT(*) FROM graphique")->fetchColumn() == 0) {
    $sample_data = [
        ['Ventes', 150], ['Ventes', 200], ['Utilisateurs', 50],
        ['Utilisateurs', 65], ['Visites', 300], ['Visites', 350],
        ['production', 120], ['achats', 180], ['emplois', 90]
    ];
    $stmt = $pdo->prepare("INSERT INTO graphique (categorie, valeur) VALUES (?, ?)");
    foreach ($sample_data as $data) {
        $stmt->execute($data);
    }
}

// Récupérer les catégories
$categories = $pdo->query("SELECT DISTINCT categorie FROM graphique")->fetchAll(PDO::FETCH_COLUMN);

// Couleurs des catégories
$category_colors = [
    'Ventes' => '#4361ee', 'Utilisateurs' => '#f72585', 'Visites' => '#4cc9f0',
    'production' => '#2ec4b6', 'achats' => '#7209b7', 'emplos' => '#f48c06'
];

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Erreur'];

    switch ($_POST['action']) {
        case 'add_data':
        case 'edit_data':
            $categorie = trim($_POST['categorie']);
            $valeur = (int)$_POST['valeur'];
            if (!empty($categorie) && $valeur > 0) {
                try {
                    if ($_POST['action'] == 'add_data') {
                        $stmt = $pdo->prepare("INSERT INTO graphique (categorie, valeur) VALUES (?, ?)");
                        $stmt->execute([$categorie, $valeur]);
                    } else {
                        $id = (int)$_POST['id'];
                        $stmt = $pdo->prepare("UPDATE graphique SET categorie = ?, valeur = ? WHERE id = ?");
                        $stmt->execute([$categorie, $valeur, $id]);
                    }
                    $response = ['success' => true, 'message' => $_POST['action'] == 'add_data' ? 'Donnée ajoutée !' : 'Donnée modifiée !'];
                } catch (PDOException $e) {
                    $response['message'] = 'Erreur base de données';
                }
            } else {
                $response['message'] = 'Données invalides';
            }
            break;

        case 'delete_data':
            $id = (int)$_POST['id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM graphique WHERE id = ?");
                $stmt->execute([$id]);
                $response = ['success' => true, 'message' => 'Donnée supprimée !'];
            } catch (PDOException $e) {
                $response['message'] = 'Erreur base de données';
            }
            break;
    }
    echo json_encode($response);
    exit;
}

// Données pour les graphiques
$chart_data = [];
foreach ($categories as $category) {
    $stmt = $pdo->prepare("SELECT AVG(valeur) as moyenne, COUNT(*) as total FROM graphique WHERE categorie = ?");
    $stmt->execute([$category]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $chart_data[$category] = [
        'moyenne' => round($result['moyenne'] ?: 0, 1),
        'total' => $result['total'] ?: 0
    ];
}

// Données détaillées et statistiques
$all_data = $pdo->query("SELECT * FROM graphique ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$stats = $pdo->query("SELECT COUNT(*) as total_entrees, AVG(valeur) as moyenne_generale, SUM(valeur) as somme_totale FROM graphique")->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css">
    <title>gestion des graphiques</title>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-chart-line"></i>
            <h1>Dashboard</h1>
        </div>
        <ul class="nav-menu">
            <li><a href="#" class="nav-link active" data-section="accueil"><i class="fas fa-home"></i><span>Accueil</span></a></li>
            <li><a href="#" class="nav-link" data-section="statistiques"><i class="fas fa-chart-bar"></i><span>Statistiques</span></a></li>
            <li><a href="#" class="nav-link" data-section="donnees"><i class="fas fa-table"></i><span>Données</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="welcome">
                <h2>Tableau de bord</h2>
                <p>Gérez vos données</p>
            </div>
            <button class="btn" onclick="openModal('addDataModal')"><i class="fas fa-plus"></i> Ajouter</button>
        </div>

        <div class="section active" id="accueil">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--primary);"><i class="fas fa-database"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_entrees'] ?: 0; ?></h3>
                        <p>Entrées</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--success);"><i class="fas fa-calculator"></i></div>
                    <div class="stat-info">
                        <h3><?php echo round($stats['moyenne_generale'] ?: 0, 1); ?></h3>
                        <p>Moyenne</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--warning);"><i class="fas fa-chart-pie"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['somme_totale'] ?: 0; ?></h3>
                        <p>Total</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="section" id="statistiques">
            <div class="charts-grid">
                <div class="dashboard-card">
                    <div class="card-title">Moyennes </div>
                    <div class="chart-container"><canvas id="barChart"></canvas></div>
                </div>
                <div class="dashboard-card">
                    <div class="card-title">Répartition </div>
                    <div class="chart-container"><canvas id="doughnutChart"></canvas></div>
                </div>
                <div class="dashboard-card">
                    <div class="card-title">Tendances </div>
                    <div class="chart-container"><canvas id="lineChart"></canvas></div>
                </div>
            </div>
        </div>

        <div class="section" id="donnees">
            <div class="dashboard-card">
                <div class="card-title">Données</div>
                <?php if ($all_data): ?>
                    <table class="data-table">
                        <thead>
                            <tr><th>ID</th><th>Catégorie</th><th>Valeur</th><th>Date</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_data as $row): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><span style="background: <?php echo $category_colors[$row['categorie']] ?? '#'.substr(md5(mt_rand()), 0, 6); ?>; color: #fff; padding: 0.2rem 0.5rem; border-radius: 10px;"><?php echo htmlspecialchars($row['categorie']); ?></span></td>
                                <td><?php echo $row['valeur']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                <td class="action-cell">
                                    <button class="btn-icon" onclick='editData(<?php echo json_encode($row); ?>)'><i class="fas fa-edit"></i></button>
                                    <button class="btn-icon delete" onclick="deleteData(<?php echo $row['id']; ?>)"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-database"></i>
                        <p>Aucune donnée</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="addDataModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addDataModal')">&times;</span>
            <h2>Ajouter</h2>
            <form id="addDataForm">
                <div class="form-group">
                    <label for="add_categorie">Catégorie :</label>
                    <select id="add_categorie" name="categorie" required>
                        <option value="">Sélectionnez</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="add_valeur">Valeur :</label>
                    <input type="number" id="add_valeur" name="valeur" min="1" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" onclick="closeModal('addDataModal')">Annuler</button>
                    <button type="submit" class="btn"><i class="fas fa-save"></i> Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editDataModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editDataModal')">&times;</span>
            <h2>Modifier</h2>
            <form id="editDataForm">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_categorie">Catégorie :</label>
                    <select id="edit_categorie" name="categorie" required>
                        <option value="">Sélectionnez</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_valeur">Valeur :</label>
                    <input type="number" id="edit_valeur" name="valeur" min="1" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" onclick="closeModal('editDataModal')">Annuler</button>
                    <button type="submit" class="btn"><i class="fas fa-save"></i> Modifier</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const navLinks = document.querySelectorAll('.nav-link');
            const sections = document.querySelectorAll('.section');
            const chartData = <?php echo json_encode($chart_data); ?>;
            const categories = <?php echo json_encode($categories); ?>;
            const colors = <?php echo json_encode($category_colors); ?>;

            // Navigation
            navLinks.forEach(link => {
                link.addEventListener('click', e => {
                    e.preventDefault();
                    const sectionId = link.getAttribute('data-section');
                    navLinks.forEach(l => l.classList.remove('active'));
                    link.classList.add('active');
                    sections.forEach(section => section.classList.toggle('active', section.id === sectionId));
                });
            });

            // Chart configuration
            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15 } },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                const value = ctx.raw;
                                const total = ctx.dataset.data.reduce((sum, val) => sum + val, 0);
                                return `${ctx.label}: ${value} (${total ? ((value / total) * 100).toFixed(1) : 0}%)`;
                            }
                        }
                    }
                }
            };

            // Bar Chart
            new Chart(document.getElementById('barChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: categories,
                    datasets: [{
                        label: 'Moyenne',
                        data: categories.map(cat => chartData[cat].moyenne),
                        backgroundColor: categories.map(cat => colors[cat] || '#' + Math.random().toString(16).slice(2, 8)),
                        borderWidth: 1
                    }]
                },
                options: {
                    ...commonOptions,
                    plugins: { ...commonOptions.plugins, legend: { display: false } },
                    scales: { y: { beginAtZero: true, title: { display: true, text: 'Moyenne' } }, x: { title: { display: true, text: 'Catégorie' } } }
                }
            });

            // Doughnut Chart
            new Chart(document.getElementById('doughnutChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: categories,
                    datasets: [{
                        data: categories.map(cat => chartData[cat].moyenne),
                        backgroundColor: categories.map(cat => colors[cat] || '#' + Math.random().toString(16).slice(2, 8)),
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: commonOptions
            });

            // Line Chart
            new Chart(document.getElementById('lineChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: categories,
                    datasets: [{
                        label: 'Moyenne',
                        data: categories.map(cat => chartData[cat].moyenne),
                        backgroundColor: 'rgba(67, 97, 238, 0.2)',
                        borderColor: categories.map(cat => colors[cat] || '#' + Math.random().toString(16).slice(2, 8))[0],
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    ...commonOptions,
                    plugins: { ...commonOptions.plugins, legend: { display: false } },
                    scales: { y: { beginAtZero: true, title: { display: true, text: 'Moyenne' } }, x: { title: { display: true, text: 'Catégorie' } } }
                }
            });

            // Modal functions
            window.openModal = id => {
                document.getElementById(id).style.display = 'block';
                document.body.style.overflow = 'hidden';
            };

            window.closeModal = id => {
                document.getElementById(id).style.display = 'none';
                document.body.style.overflow = 'auto';
                document.getElementById(id === 'addDataModal' ? 'addDataForm' : 'editDataForm').reset();
            };

            window.onclick = e => {
                if (e.target.classList.contains('modal')) {
                    closeModal('addDataModal');
                    closeModal('editDataModal');
                }
            };

            // Form submissions
            ['addDataForm', 'editDataForm'].forEach(id => {
                document.getElementById(id).addEventListener('submit', async e => {
                    e.preventDefault();
                    const formData = new FormData(e.target);
                    formData.append('action', id === 'addDataForm' ? 'add_data' : 'edit_data');
                    try {
                        const response = await fetch('', { method: 'POST', body: formData });
                        const data = await response.json();
                        showNotification(data.message, data.success ? 'success' : 'error');
                        if (data.success) {
                            closeModal(id === 'addDataForm' ? 'addDataModal' : 'editDataModal');
                            setTimeout(() => location.reload(), 1000);
                        }
                    } catch {
                        showNotification('Erreur de connexion', 'error');
                    }
                });
            });

            // Edit and delete functions
            window.editData = data => {
                document.getElementById('edit_id').value = data.id;
                document.getElementById('edit_categorie').value = data.categorie;
                document.getElementById('edit_valeur').value = data.valeur;
                openModal('editDataModal');
            };

            window.deleteData = async id => {
                if (!confirm('Supprimer cette donnée ?')) return;
                const formData = new FormData();
                formData.append('action', 'delete_data');
                formData.append('id', id);
                try {
                    const response = await fetch('', { method: 'POST', body: formData });
                    const data = await response.json();
                    showNotification(data.message, data.success ? 'success' : 'error');
                    if (data.success) setTimeout(() => location.reload(), 1000);
                } catch {
                    showNotification('Erreur de connexion', 'error');
                }
            };

            // Notification
            const showNotification = (message, type) => {
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${message}`;
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 3000);
            };
        });
    </script>
</body>
</html>