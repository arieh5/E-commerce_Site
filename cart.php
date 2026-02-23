<?php
session_start();
include 'config.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Gérer l'ajout au panier via GET ou POST
if (isset($_GET['add']) || (isset($_POST['action']) && $_POST['action'] === 'add' && isset($_POST['id_billet']))) {
    $id_billet = isset($_GET['add']) ? $_GET['add'] : $_POST['id_billet'];
    
    // Vérifier si le billet est disponible
    $sql = "SELECT vendu FROM Billet WHERE id_billet = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_billet);
    $stmt->execute();
    $result = $stmt->get_result();
    $ticket = $result->fetch_assoc();
    
    if (!$result->num_rows) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'count' => count($_SESSION['cart']),
            'message' => 'Ce billet n\'existe pas.'
        ]);
        exit;
    }
    
    if ($ticket['vendu']) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'count' => count($_SESSION['cart']),
            'message' => 'Désolé, ce billet a déjà été vendu.'
        ]);
        exit;
    }
    
    if (!in_array($id_billet, $_SESSION['cart'])) {
        $_SESSION['cart'][] = $id_billet;
        $message = 'Billet ajouté au panier';
    } else {
        $message = 'Ce billet est déjà dans votre panier';
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'count' => count($_SESSION['cart']),
        'message' => $message
    ]);
    exit;
}

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'remove':
            if (isset($_POST['id_billet'])) {
                $key = array_search($_POST['id_billet'], $_SESSION['cart']);
                if ($key !== false) {
                    unset($_SESSION['cart'][$key]);
                    $_SESSION['cart'] = array_values($_SESSION['cart']);
                }
            }
            $cartCount = count($_SESSION['cart']);
            $message = 'Billet retiré du panier';
            if ($cartCount === 0) {
                $message .= '. Votre panier est maintenant vide.';
            } else if ($cartCount === 1) {
                $message .= '. Il vous reste 1 billet dans votre panier.';
            } else {
                $message .= ". Il vous reste $cartCount billets dans votre panier.";
            }
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'count' => $cartCount,
                    'message' => $message
                ]);
            } else {
                $_SESSION['success'] = $message;
                header('Location: panier.php');
            }
            exit;
            break;
    }
}

if (isset($_GET['clear'])) {
    $cartCount = count($_SESSION['cart']);
    if ($cartCount > 0) {
        $_SESSION['cart'] = [];
        $_SESSION['success'] = $cartCount > 1 
            ? "$cartCount billets ont été retirés de votre panier." 
            : "Le billet a été retiré de votre panier.";
    }
    header('Location: panier.php');
    exit;
}

// Vérifier les billets vendus et nettoyer le panier si nécessaire
function checkSoldTickets() {
    global $conn;
    if (!empty($_SESSION['cart'])) {
        $placeholders = str_repeat('?,', count($_SESSION['cart']) - 1) . '?';
        $sql = "SELECT id_billet FROM Billet WHERE id_billet IN ($placeholders) AND vendu = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(str_repeat('i', count($_SESSION['cart'])), ...$_SESSION['cart']);
        $stmt->execute();
        $result = $stmt->get_result();
        $soldTickets = $result->fetch_all(MYSQLI_ASSOC);
        
        if (!empty($soldTickets)) {
            $soldIds = array_column($soldTickets, 'id_billet');
            $_SESSION['cart'] = array_values(array_diff($_SESSION['cart'], $soldIds));
            return true;
        }
    }
    return false;
}

// Vérifier le panier avant chaque action importante
if (!empty($_SESSION['cart'])) {
    if (checkSoldTickets()) {
        $cartCount = count($_SESSION['cart']);
        if ($cartCount === 0) {
            $_SESSION['error'] = 'Désolé, tous les billets de votre panier ont été vendus. Veuillez sélectionner d\'autres billets.';
        } else {
            $message = 'Certains billets de votre panier ont été vendus et ont été retirés automatiquement.';
            if ($cartCount === 1) {
                $message .= '\nIl vous reste 1 billet dans votre panier.';
            } else {
                $message .= "\nIl vous reste $cartCount billets dans votre panier.";
            }
            $_SESSION['error'] = $message;
        }
        
        if (!isset($_POST['action']) || $_POST['action'] !== 'add') {
            header('Location: panier.php');
            exit;
        }
    }
}

// Gérer la redirection vers le paiement
if (isset($_POST['action']) && $_POST['action'] === 'checkout') {
    if (!empty($_SESSION['cart'])) {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['redirect_after_login'] = 'payment.php';
            header('Location: login.php');
            exit;
        }
        
        // Rediriger vers la page de paiement
        header('Location: payment.php');
        exit;
    } else {
        header('Location: panier.php');
        exit;
    }
}
?>
