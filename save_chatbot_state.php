<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['chatbot_open'] = isset($_POST['chatbot_open']) && $_POST['chatbot_open'] === '1';
    echo 'OK';
} else {
    http_response_code(400);
    echo 'Error';
}
?>