<?php
/**
 * Common App Layout Footer & Scripts
 * Visitor Management System (VMS)
 */
?>
    </div><!-- .app-body -->
  </main><!-- .app-main -->
</div><!-- .app-container -->

<!-- Toast Notification Container -->
<div id="toast-container"></div>

<!-- Core Scripts -->
<script src="assets/js/api.js"></script>
<script src="assets/js/ui.js"></script>
<?php if (!empty($extraJs)): ?>
  <script src="<?= escapeHtml($extraJs) ?>"></script>
<?php endif; ?>

</body>
</html>

