<?php
use App\Core\CSRF;
?>
<script>window.CSRF_TOKEN = <?php echo json_encode(CSRF::token(), JSON_UNESCAPED_SLASHES); ?>;</script>