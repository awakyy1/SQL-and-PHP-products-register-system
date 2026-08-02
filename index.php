<?php
declare(strict_types=1);

require __DIR__ . '/conn.php';

const SECTORS = ['s1', 's2', 's3', 's4'];

function decimalValue(mixed $value): ?string
{
    $text = trim((string) $value);
    return preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $text) === 1 ? $text : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($id === false || $id === null) {
            redirectWithMessage('Invalid product identifier.');
        }

        $statement = $conn->prepare('DELETE FROM products WHERE id = :id');
        $statement->execute(['id' => $id]);
        redirectWithMessage('Product deleted.');
    }

    if ($action === 'save') {
        $idText = trim((string) ($_POST['id'] ?? ''));
        $id = $idText === '' ? null : filter_var($idText, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $name = trim((string) ($_POST['name'] ?? ''));
        $sector = (string) ($_POST['sector'] ?? '');
        $cost = decimalValue($_POST['cost'] ?? null);
        $salePrice = decimalValue($_POST['sale_price'] ?? null);
        $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0, 'max_range' => 999999],
        ]);

        if (($idText !== '' && $id === false)
            || $name === ''
            || mb_strlen($name) > 80
            || !in_array($sector, SECTORS, true)
            || $cost === null
            || $salePrice === null
            || $stock === false) {
            redirectWithMessage('Check all product fields and try again.');
        }

        $parameters = [
            'cost' => $cost,
            'name' => $name,
            'sale_price' => $salePrice,
            'sector' => $sector,
            'stock' => $stock,
        ];

        if ($id === null) {
            $statement = $conn->prepare(
                'INSERT INTO products (name, sector, cost, sale_price, stock) '
                . 'VALUES (:name, :sector, :cost, :sale_price, :stock)'
            );
            $message = 'Product created.';
        } else {
            $statement = $conn->prepare(
                'UPDATE products SET name = :name, sector = :sector, cost = :cost, '
                . 'sale_price = :sale_price, stock = :stock WHERE id = :id'
            );
            $parameters['id'] = $id;
            $message = 'Product updated.';
        }

        $statement->execute($parameters);
        redirectWithMessage($message);
    }

    http_response_code(400);
    exit('Unknown action.');
}

$editing = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
if ($editId !== false && $editId !== null) {
    $statement = $conn->prepare('SELECT * FROM products WHERE id = :id');
    $statement->execute(['id' => $editId]);
    $editing = $statement->fetch() ?: null;
}

$products = $conn->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();
$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Product Catalog</title>
</head>
<body>
<main>
    <h1>Product Catalog</h1>
    <?php if (is_string($message)): ?>
        <p role="status"><?= escape($message) ?></p>
    <?php endif; ?>

    <form method="post" action="index.php">
        <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= escape((string) ($editing['id'] ?? '')) ?>">
        <label>Name <input name="name" maxlength="80" required value="<?= escape((string) ($editing['name'] ?? '')) ?>"></label>
        <label>Sector
            <select name="sector" required>
                <?php foreach (SECTORS as $sector): ?>
                    <option value="<?= escape($sector) ?>" <?= ($editing['sector'] ?? '') === $sector ? 'selected' : '' ?>><?= escape(strtoupper($sector)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Cost <input type="number" name="cost" min="0" max="99999999.99" step="0.01" required value="<?= escape((string) ($editing['cost'] ?? '')) ?>"></label>
        <label>Sale price <input type="number" name="sale_price" min="0" max="99999999.99" step="0.01" required value="<?= escape((string) ($editing['sale_price'] ?? '')) ?>"></label>
        <label>Stock <input type="number" name="stock" min="0" max="999999" required value="<?= escape((string) ($editing['stock'] ?? '0')) ?>"></label>
        <button type="submit"><?= $editing === null ? 'Create product' : 'Update product' ?></button>
        <?php if ($editing !== null): ?><a href="index.php">Cancel</a><?php endif; ?>
    </form>

    <table>
        <thead><tr><th>Name</th><th>Sector</th><th>Cost</th><th>Sale price</th><th>Stock</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($products as $product): ?>
            <tr>
                <td><?= escape((string) $product['name']) ?></td>
                <td><?= escape((string) $product['sector']) ?></td>
                <td><?= escape(number_format((float) $product['cost'], 2, '.', '')) ?></td>
                <td><?= escape(number_format((float) $product['sale_price'], 2, '.', '')) ?></td>
                <td><?= escape((string) $product['stock']) ?></td>
                <td>
                    <a href="?edit=<?= escape((string) $product['id']) ?>">Edit</a>
                    <form method="post" action="index.php" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= escape((string) $product['id']) ?>">
                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($products === []): ?>
            <tr><td colspan="6">No products found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</main>
</body>
</html>
