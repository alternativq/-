<?php
// ============================================================================
// 1. ПОДКЛЮЧЕНИЕ К БАЗЕ ДАННЫХ
// ============================================================================
$host = 'localhost';
$db   = 'maga';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$opt = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $opt);
} catch (\PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// ============================================================================
// 2. ОПРЕДЕЛЕНИЕ РОЛИ (ПЕРЕКЛЮЧАТЕЛЬ В ШАПКЕ)
// ============================================================================
// По умолчанию роль менеджера магазина (id=1)
$role = $_GET['role'] ?? 'store'; 
$current_user_id = 1;

if ($role == 'office') {
    $current_user_id = 2; // Елена
} elseif ($role == 'hr') {
    $current_user_id = 3; // Мария
}

// Получаем данные текущего пользователя
$stmt = $pdo->prepare("SELECT u.*, s.name as store_name FROM users u LEFT JOIN stores s ON u.store_id = s.id WHERE u.id = ?");
$stmt->execute([$current_user_id]);
$user = $stmt->fetch();

// Получаем данные для отображения в зависимости от роли
$tasks = [];
$hr_requests = [];
$specialists = [];

if ($role == 'store') {
    // Задания для магазина
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE store_id = ? ORDER BY id DESC");
    $stmt->execute([$user['store_id']]);
    $tasks = $stmt->fetchAll();
} elseif ($role == 'office') {
    // Все активные задания для офиса
    $stmt = $pdo->query("SELECT t.*, s.name as store_name FROM tasks t JOIN stores s ON t.store_id = s.id WHERE t.status != 'completed' ORDER BY t.id DESC");
    $tasks = $stmt->fetchAll();
    
    // Заявки HR
    $stmt = $pdo->query("SELECT hr.*, s.name as store_name FROM hr_requests hr JOIN stores s ON hr.store_id = s.id WHERE hr.status = 'open'");
    $hr_requests = $stmt->fetchAll();
} elseif ($role == 'hr') {
    // Все заявки HR
    $stmt = $pdo->query("SELECT hr.*, s.name as store_name FROM hr_requests hr JOIN stores s ON hr.store_id = s.id ORDER BY hr.id DESC");
    $hr_requests = $stmt->fetchAll();
    
    // Специалисты
    $stmt = $pdo->query("SELECT * FROM specialists ORDER BY id DESC");
    $specialists = $stmt->fetchAll();
}

// Функция для красивого статуса
function getStatusClass($status) {
    $map = [
        'draft' => 'secondary',
        'new' => 'primary',
        'in_progress' => 'warning',
        'awaiting_assignment' => 'info',
        'completed' => 'success',
        'open' => 'primary',
        'closed' => 'secondary'
    ];
    return $map[$status] ?? 'secondary';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ServiceWorks - Управление сервисными работами</title>
    <style>
        :root {
            --primary: #2563eb;
            --secondary: #64748b;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #06b6d4;
            --light: #f8fafc;
            --dark: #1e293b;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: var(--light); color: var(--dark); }
        
        /* Header */
        .header { background: white; padding: 1rem 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: bold; color: var(--primary); display: flex; align-items: center; gap: 0.5rem; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .role-switcher { display: flex; gap: 0.5rem; }
        .role-btn { padding: 0.5rem 1rem; border: 1px solid var(--secondary); border-radius: 6px; background: white; cursor: pointer; text-decoration: none; color: var(--dark); font-size: 0.9rem; }
        .role-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
        .user-name { font-weight: 600; }
        
        /* Container */
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        
        /* Welcome Block */
        .welcome { background: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .welcome h1 { margin-bottom: 0.5rem; }
        .welcome p { color: var(--secondary); }
        
        /* Buttons */
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-block { width: 100%; text-align: center; }
        
        /* Cards Grid */
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        
        /* Task Card */
        .card { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 4px solid var(--primary); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .task-number { font-weight: bold; color: var(--primary); }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
        .status-primary { background: #dbeafe; color: var(--primary); }
        .status-warning { background: #fef3c7; color: var(--warning); }
        .status-success { background: #dcfce7; color: var(--success); }
        .status-info { background: #cffafe; color: var(--info); }
        .status-secondary { background: #f1f5f9; color: var(--secondary); }
        
        .card h3 { margin-bottom: 0.5rem; }
        .card-info { color: var(--secondary); font-size: 0.9rem; margin-bottom: 0.25rem; }
        .card-actions { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; }
        .card-actions a { color: var(--primary); text-decoration: none; font-weight: 600; }
        
        /* Sections */
        .section-title { font-size: 1.25rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; }
        
        /* Form */
        .form-card { background: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        
        /* Footer */
        .footer { text-align: center; padding: 2rem; color: var(--secondary); border-top: 1px solid #e2e8f0; margin-top: 2rem; }
        
        /* Specialists Table */
        .table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; }
        .table th, .table td { padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .table th { background: #f8fafc; font-weight: 600; }
    </style>
</head>
<body>

    <!-- Шапка -->
    <header class="header">
        <div class="logo">📋 ServiceWorks</div>
        <div class="user-info">
            <div class="role-switcher">
                <a href="?role=store" class="role-btn <?= $role == 'store' ? 'active' : '' ?>">🏪 Магазин</a>
                <a href="?role=office" class="role-btn <?= $role == 'office' ? 'active' : '' ?>">🏢 Офис</a>
                <a href="?role=hr" class="role-btn <?= $role == 'hr' ? 'active' : '' ?>">👥 HR</a>
            </div>
            <div class="user-name">👤 <?= htmlspecialchars($user['full_name']) ?> (<?= $user['role'] ?>)</div>
        </div>
    </header>

    <div class="container">
        
        <!-- Приветствие -->
        <div class="welcome">
            <h1>Добро пожаловать, <?= htmlspecialchars($user['full_name']) ?>! 👋</h1>
            <p>
                <?php if ($user['store_name']): ?>
                    Управляющий магазином "<?= htmlspecialchars($user['store_name']) ?>"
                <?php elseif ($user['role'] == 'office_manager'): ?>
                    Управляющий офисом
                <?php else: ?>
                    Специалист HR-отдела
                <?php endif; ?>
            </p>
        </div>

        <!-- ==================================================================== -->
        <!-- ИНТЕРФЕЙС УПРАВЛЯЮЩЕГО МАГАЗИНОМ -->
        <!-- ==================================================================== -->
        <?php if ($role == 'store'): ?>
            
            <div class="section-title">
                <h2>Активные задания</h2>
                <a href="#create" class="btn btn-primary">+ Создать новое задание</a>
            </div>

            <div class="cards-grid">
                <?php if (count($tasks) > 0): ?>
                    <?php foreach ($tasks as $task): ?>
                    <div class="card" style="border-left-color: <?= $task['status'] == 'completed' ? 'var(--success)' : 'var(--primary)' ?>">
                        <div class="card-header">
                            <span class="task-number"><?= htmlspecialchars($task['task_number']) ?></span>
                            <span class="status-badge status-<?= getStatusClass($task['status']) ?>">
                                <?= $task['status'] == 'in_progress' ? 'В РАБОТЕ' : ($task['status'] == 'new' ? 'НОВЫЙ' : $task['status']) ?>
                            </span>
                        </div>
                        <h3><?= htmlspecialchars($task['title']) ?></h3>
                        <p class="card-info">📍 Магазин "<?= htmlspecialchars($user['store_name']) ?>"</p>
                        <p class="card-info">📅 <?= date('d M Y', strtotime($task['start_date'])) ?> → <?= date('d M Y', strtotime($task['end_date'])) ?></p>
                        <div class="card-actions">
                            <a href="#">[Подробнее →]</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Нет активных заданий</p>
                <?php endif; ?>
            </div>

            <!-- Форма создания задания -->
            <div id="create" class="form-card">
                <h2>Создание нового задания</h2>
                <form action="" method="POST">
                    <div class="form-group">
                        <label>Название работ</label>
                        <input type="text" name="title" placeholder="Например: Уборка территории" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Категория / Должность</label>
                            <select name="category">
                                <option>Уборщик</option>
                                <option>Инвентаризатор</option>
                                <option>Сантехник</option>
                                <option>Электрик</option>
                                <option>Разнорабочий</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Статус</label>
                            <select name="status">
                                <option value="draft">Черновик</option>
                                <option value="new">Новый</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Дата начала</label>
                            <input type="date" name="start_date" required>
                        </div>
                        <div class="form-group">
                            <label>Дата окончания</label>
                            <input type="date" name="end_date" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Дополнительные требования</label>
                        <textarea name="requirements" rows="3" placeholder="Опыт работы, наличие инструмента..."></textarea>
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" name="action" value="draft" class="btn btn-secondary">Сохранить черновик</button>
                        <button type="submit" name="action" value="new" class="btn btn-primary">Отправить в офис →</button>
                    </div>
                </form>
            </div>

        <!-- ==================================================================== -->
        <!-- ИНТЕРФЕЙС УПРАВЛЯЮЩЕГО ОФИСОМ -->
        <!-- ==================================================================== -->
        <?php elseif ($role == 'office'): ?>
            
            <div class="section-title">
                <h2>Задания в работе (ожидают назначения)</h2>
            </div>

            <div class="cards-grid">
                <?php foreach ($tasks as $task): ?>
                <div class="card">
                    <div class="card-header">
                        <span class="task-number"><?= htmlspecialchars($task['task_number']) ?></span>
                        <span class="status-badge status-<?= getStatusClass($task['status']) ?>"><?= $task['status'] ?></span>
                    </div>
                    <h3><?= htmlspecialchars($task['title']) ?></h3>
                    <p class="card-info">📍 Магазин "<?= htmlspecialchars($task['store_name']) ?>"</p>
                    <p class="card-info">📅 <?= $task['start_date'] ?> → <?= $task['end_date'] ?></p>
                    <div class="card-actions">
                        <a href="#" class="btn btn-primary btn-block">Найти сотрудников</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($hr_requests) > 0): ?>
            <div class="section-title">
                <h2>Заявки в HR (ожидают подбора)</h2>
            </div>
            <div class="cards-grid">
                <?php foreach ($hr_requests as $req): ?>
                <div class="card" style="border-left-color: var(--info)">
                    <h3><?= htmlspecialchars($req['position_title']) ?></h3>
                    <p class="card-info">📍 Магазин "<?= htmlspecialchars($req['store_name']) ?>"</p>
                    <p class="card-info">📅 <?= $req['start_date'] ?> → <?= $req['end_date'] ?></p>
                    <div class="card-actions">
                        <span class="status-badge status-primary">В работе</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        <!-- ==================================================================== -->
        <!-- ИНТЕРФЕЙС HR-СПЕЦИАЛИСТА -->
        <!-- ==================================================================== -->
        <?php elseif ($role == 'hr'): ?>
            
            <div class="section-title">
                <h2>Заявки на подбор</h2>
            </div>

            <div class="cards-grid">
                <?php foreach ($hr_requests as $req): ?>
                <div class="card" style="border-left-color: var(--success)">
                    <div class="card-header">
                        <span class="task-number"><?= htmlspecialchars($req['request_number']) ?></span>
                        <span class="status-badge status-<?= getStatusClass($req['status']) ?>"><?= $req['status'] ?></span>
                    </div>
                    <h3><?= htmlspecialchars($req['position_title']) ?></h3>
                    <p class="card-info">📍 Магазин "<?= htmlspecialchars($req['store_name']) ?>"</p>
                    <p class="card-info">📅 <?= $req['start_date'] ?> → <?= $req['end_date'] ?></p>
                    <div class="card-actions">
                        <a href="#" class="btn btn-primary btn-block">Взять в работу</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="section-title">
                <h2>База специалистов</h2>
                <a href="#add-specialist" class="btn btn-primary">+ Добавить</a>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>ФИО</th>
                        <th>Специализация</th>
                        <th>Телефон</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($specialists as $spec): ?>
                    <tr>
                        <td><?= htmlspecialchars($spec['full_name']) ?></td>
                        <td><?= htmlspecialchars($spec['specialization']) ?></td>
                        <td><?= htmlspecialchars($spec['phone']) ?></td>
                        <td>
                            <?php if ($spec['has_medical_book']): ?>✅ Медкнижка<?php endif; ?>
                            <?php if ($spec['has_workwear']): ?>✅ Спецодежда<?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div id="add-specialist" class="form-card">
                <h2>Добавить нового специалиста</h2>
                <form action="" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>ФИО</label>
                            <input type="text" name="full_name" required>
                        </div>
                        <div class="form-group">
                            <label>Специализация</label>
                            <select name="specialization">
                                <option>Сантехник</option>
                                <option>Электрик</option>
                                <option>Клинер</option>
                                <option>Разнорабочий</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Телефон</label>
                            <input type="text" name="phone">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Сохранить в базу "Персонал"</button>
                </form>
            </div>

        <?php endif; ?>

    </div>

    <footer class="footer">
        © 2026 ServiceWorks — Управление сервисными работами. Демонстрационный прототип.
    </footer>

</body>
</html>