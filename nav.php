<?php
$navItems = [
    'index.php' => 'Journal',
    'on-this-day.php' => 'On This Day',
    'stats.php' => 'Stats'
];

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav>
    <ul class="nav">
        <?php foreach ($navItems as $file => $label): ?>
            <li>
                <a href="<?php echo $file; ?>" class="<?php echo $file === $currentPage ? 'current' : ''; ?>">
                    <?php echo $label; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>