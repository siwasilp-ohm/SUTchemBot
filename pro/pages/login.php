<?php pro_header('Pro Login'); ?>
<main class="container login-wrap">
    <section class="card">
        <h2>Sign in to Pro Console</h2>
        <p class="muted">Admin / CEO / Lab Manager / User</p>
        <?php if (!empty($error)): ?><p class="flash error"><?= pro_h($error) ?></p><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= pro_h(pro_csrf_token()) ?>">
            <div class="form-row"><input name="username" autocomplete="username" placeholder="username" required style="width:100%"></div><br>
            <div class="form-row"><input type="password" name="password" autocomplete="current-password" placeholder="password" required style="width:100%"></div><br>
            <button class="btn" type="submit">เข้าสู่ระบบ</button>
        </form>
        <p class="muted">Demo users จะถูก seed ในสคริปต์ฐานข้อมูล (รหัสผ่าน: <code>Password123!</code>)</p>
    </section>
</main>
<?php pro_footer(); ?>
