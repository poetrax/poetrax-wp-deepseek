<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - Poetrax</title>
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <h1>Вход</h1>
        <form id="login-form">
            <input type="text" name="login" placeholder="Логин или email" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit">Войти</button>
        </form>
        <p>Нет аккаунта? <a href="/register">Регистрация</a></p>
    </div>
    <script src="/assets/js/auth.js"></script>
</body>
</html>
