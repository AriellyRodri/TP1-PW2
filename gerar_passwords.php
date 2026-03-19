<?php

require_once 'acessoBD.php';



echo "<h2>Atualizar Passwords com MD5</h2><ul>";

foreach ($utilizadores as $login => $plaintext) {
    $hash = md5($plaintext);
    $stmt = $pdo->prepare("UPDATE users SET pwd=? WHERE login=?");
    if ($stmt->execute([$hash, $login])) {
        echo "<li>✅ <strong>$login</strong> → password '$plaintext' atualizada</li>";
    } else {
        echo "<li>❌ <strong>$login</strong> → erro ao atualizar</li>";
    }
}

echo "</ul><p><strong>Concluído! Apague este ficheiro agora.</strong></p>";
?>