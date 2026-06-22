<?php
/**
 * Redirecionamento — conteúdo de formulários está em index.php?p=formularios
 */
header('Location: index.php?p=formularios', true, 302);
exit;
