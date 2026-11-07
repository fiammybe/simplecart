<?php
/** Runs the real UPDATE of closeScannedOrder() on SQLite, so the status condition that keeps two admins apart is executed */
class ScanSqliteDb
{
    public $pdo;
    private $affected = 0;

    public function __construct()
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec('CREATE TABLE x_simplecart_order (order_id INTEGER PRIMARY KEY, status TEXT, scanned_at INT DEFAULT 0, scanned_by INT DEFAULT 0)');
        $this->pdo->exec("INSERT INTO x_simplecart_order (order_id, status) VALUES (1, 'paid'), (2, 'pending'), (3, 'closed')");
    }

    public function prefix($name) { return 'x_' . $name; }

    public function queryF($sql)
    {
        $statement = $this->pdo->query($sql);
        $this->affected = $statement->rowCount();

        return $statement;
    }

    public function getAffectedRows() { return $this->affected; }
}

function scanRow(ScanSqliteDb $db, int $id): string
{
    return implode('/', $db->pdo->query("SELECT status, scanned_by FROM x_simplecart_order WHERE order_id = {$id}")->fetch(PDO::FETCH_NUM));
}

test('a paid order is closed once, by the first admin only', function () {
    $db = new ScanSqliteDb();
    $handler = new SimplecartOrderHandler($db);

    assertSame(true, $handler->closeScannedOrder(1, 7));
    assertSame(false, $handler->closeScannedOrder(1, 8), 'a second admin does not close it again');
    assertSame('closed/7', scanRow($db, 1));
});

test('orders that are not paid cannot be closed from the scan page', function () {
    $db = new ScanSqliteDb();
    $handler = new SimplecartOrderHandler($db);

    assertSame(false, $handler->closeScannedOrder(2, 7));
    assertSame(false, $handler->closeScannedOrder(3, 7));
    assertSame(false, $handler->closeScannedOrder(99, 7));
    assertSame('pending/0', scanRow($db, 2));
    assertSame('closed/0', scanRow($db, 3));
});
