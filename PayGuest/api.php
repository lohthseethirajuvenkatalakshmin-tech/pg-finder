<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function request_data(): array
{
    $input = file_get_contents('php://input');
    $data = json_decode($input ?: '', true);
    return is_array($data) ? $data : $_POST;
}

function require_csrf(): void
{
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($provided) || !hash_equals(csrf_token(), $provided)) {
        respond(['error' => 'Your session has expired. Refresh the page and try again.'], 419);
    }
}

function normalize_property(array $property): array
{
    $property['id'] = (int) $property['id'];
    $property['price'] = (int) $property['price'];
    $property['rating'] = (float) $property['rating'];
    $property['review_count'] = (int) $property['review_count'];
    $property['featured'] = (bool) $property['featured'];
    $property['amenities'] = $property['amenities']
        ? explode('|', $property['amenities'])
        : [];
    $property['is_interested'] = (bool) $property['is_interested'];
    unset($property['amenities_csv']);
    return $property;
}

function property_select(): string
{
    return "SELECT p.id, p.name, p.city, p.neighborhood, p.address, p.price,
                   p.gender, p.rating, p.review_count, p.description,
                   p.featured, p.image_url,
                   GROUP_CONCAT(DISTINCT a.name ORDER BY a.name SEPARATOR '|') AS amenities,
                   EXISTS (
                       SELECT 1 FROM interested_users iu
                       WHERE iu.property_id = p.id AND iu.user_id = :user_id
                   ) AS is_interested
            FROM properties p
            LEFT JOIN property_amenities pa ON pa.property_id = p.id
            LEFT JOIN amenities a ON a.id = pa.amenity_id";
}

try {
    start_app_session();
    $db = database();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = $_GET['action'] ?? '';

    if ($method === 'GET' && $action === 'properties') {
        $conditions = [];
        $parameters = [':user_id' => current_user()['id'] ?? 0];
        $city = trim((string) ($_GET['city'] ?? ''));
        $gender = trim((string) ($_GET['gender'] ?? ''));
        $budget = trim((string) ($_GET['budget'] ?? ''));
        $search = trim((string) ($_GET['q'] ?? ''));

        if ($city !== '') {
            $conditions[] = 'p.city = :city';
            $parameters[':city'] = $city;
        }
        if (in_array($gender, ['Women', 'Men', 'Any'], true)) {
            $conditions[] = 'p.gender IN (:gender, "Any")';
            $parameters[':gender'] = $gender;
        }
        if ($budget === 'under-5') {
            $conditions[] = 'p.price < 5000';
        } elseif ($budget === '5-8') {
            $conditions[] = 'p.price >= 5000 AND p.price <= 8000';
        } elseif ($budget === '8-12') {
            $conditions[] = 'p.price > 8000 AND p.price <= 12000';
        } elseif ($budget === 'over-12') {
            $conditions[] = 'p.price > 12000';
        }
        if ($search !== '') {
            $conditions[] = '(p.name LIKE :search_name OR p.city LIKE :search_city OR p.neighborhood LIKE :search_neighborhood)';
            $searchTerm = '%' . $search . '%';
            $parameters[':search_name'] = $searchTerm;
            $parameters[':search_city'] = $searchTerm;
            $parameters[':search_neighborhood'] = $searchTerm;
        }

        $sql = property_select();
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sort = ($_GET['sort'] ?? '') === 'newest'
            ? 'p.created_at DESC, p.id DESC'
            : 'p.featured DESC, p.rating DESC, p.id ASC';
        $sql .= ' GROUP BY p.id ORDER BY ' . $sort;
        $statement = $db->prepare($sql);
        $statement->execute($parameters);
        $properties = array_map('normalize_property', $statement->fetchAll());

        respond([
            'properties' => $properties,
            'user' => current_user(),
            'csrfToken' => csrf_token(),
        ]);
    }

    if ($method === 'GET' && $action === 'property') {
        $propertyId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$propertyId) {
            respond(['error' => 'A valid property ID is required.'], 400);
        }

        $statement = $db->prepare(property_select() . ' WHERE p.id = :property_id GROUP BY p.id');
        $statement->execute([
            ':user_id' => current_user()['id'] ?? 0,
            ':property_id' => $propertyId,
        ]);
        $property = $statement->fetch();
        if (!$property) {
            respond(['error' => 'This stay could not be found.'], 404);
        }

        $images = $db->prepare(
            'SELECT image_url, caption FROM property_images WHERE property_id = :property_id ORDER BY sort_order'
        );
        $images->execute([':property_id' => $propertyId]);
        $property['images'] = $images->fetchAll();
        respond(['property' => normalize_property($property)]);
    }

    if ($method === 'GET' && $action === 'shortlist') {
        $user = current_user();
        if (!$user) {
            respond(['error' => 'Sign in to see your saved stays.'], 401);
        }

        $statement = $db->prepare(
            property_select() . ' INNER JOIN interested_users own ON own.property_id = p.id
              AND own.user_id = :shortlist_user_id
          WHERE 1 = 1 GROUP BY p.id ORDER BY p.id DESC'
        );
        $statement->execute([
            ':user_id' => $user['id'],
            ':shortlist_user_id' => $user['id'],
        ]);
        respond(['properties' => array_map('normalize_property', $statement->fetchAll())]);
    }

    if ($method === 'POST') {
        require_csrf();
        $data = request_data();

        if ($action === 'register') {
            $name = trim((string) ($data['name'] ?? ''));
            $email = strtolower(trim((string) ($data['email'] ?? '')));
            $phone = trim((string) ($data['phone'] ?? ''));
            $password = (string) ($data['password'] ?? '');

            if (preg_match('/\A.{1,100}\z/us', $name) !== 1 || !filter_var($email, FILTER_VALIDATE_EMAIL)
                || strlen($password) < 8 || strlen($password) > 72) {
                respond(['error' => 'Add your name, a valid email, and a password of at least 8 characters.'], 422);
            }

            try {
                $statement = $db->prepare(
                    'INSERT INTO users (name, email, phone, password_hash)
                     VALUES (:name, :email, :phone, :password_hash)'
                );
                $statement->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':phone' => $phone !== '' ? $phone : null,
                    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    respond(['error' => 'An account with that email already exists.'], 409);
                }
                throw $exception;
            }

            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int) $db->lastInsertId(),
                'name' => $name,
                'email' => $email,
            ];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            respond(['user' => $_SESSION['user'], 'csrfToken' => csrf_token()], 201);
        }

        if ($action === 'login') {
            $email = strtolower(trim((string) ($data['email'] ?? '')));
            $password = (string) ($data['password'] ?? '');
            $statement = $db->prepare('SELECT id, name, email, password_hash FROM users WHERE email = :email');
            $statement->execute([':email' => $email]);
            $account = $statement->fetch();

            if (!$account || !password_verify($password, $account['password_hash'])) {
                respond(['error' => 'That email and password combination was not recognised.'], 401);
            }

            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int) $account['id'],
                'name' => $account['name'],
                'email' => $account['email'],
            ];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            respond(['user' => $_SESSION['user'], 'csrfToken' => csrf_token()]);
        }

        if ($action === 'logout') {
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            respond(['user' => null, 'csrfToken' => csrf_token()]);
        }

        if ($action === 'interest') {
            $user = current_user();
            if (!$user) {
                respond(['error' => 'Sign in to save a stay to your shortlist.'], 401);
            }
            $propertyId = filter_var($data['propertyId'] ?? null, FILTER_VALIDATE_INT);
            if (!$propertyId) {
                respond(['error' => 'A valid property ID is required.'], 400);
            }

            $exists = $db->prepare('SELECT id FROM properties WHERE id = :id');
            $exists->execute([':id' => $propertyId]);
            if (!$exists->fetch()) {
                respond(['error' => 'This stay could not be found.'], 404);
            }

            $check = $db->prepare(
                'SELECT 1 FROM interested_users WHERE user_id = :user_id AND property_id = :property_id'
            );
            $check->execute([':user_id' => $user['id'], ':property_id' => $propertyId]);
            if ($check->fetchColumn()) {
                $statement = $db->prepare(
                    'DELETE FROM interested_users WHERE user_id = :user_id AND property_id = :property_id'
                );
                $statement->execute([':user_id' => $user['id'], ':property_id' => $propertyId]);
                $interested = false;
            } else {
                $statement = $db->prepare(
                    'INSERT INTO interested_users (user_id, property_id) VALUES (:user_id, :property_id)'
                );
                $statement->execute([':user_id' => $user['id'], ':property_id' => $propertyId]);
                $interested = true;
            }

            respond(['propertyId' => (int) $propertyId, 'interested' => $interested]);
        }
    }

    respond(['error' => 'The requested action is not available.'], 404);
} catch (Throwable $exception) {
    error_log(sprintf('[student-stays] %s: %s', get_class($exception), $exception->getMessage()));
    respond(['error' => 'We could not complete that request. Check the database connection and try again.'], 500);
}
