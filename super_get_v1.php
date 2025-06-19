 (cd "$(git rev-parse --show-toplevel)" && git apply --3way <<'EOF' 
diff --git a//dev/null b/super_get_api.php
index 0000000000000000000000000000000000000000..e67963a1b28a9464659ce3500974dab2bcb85026 100644
--- a//dev/null
+++ b/super_get_api.php
@@ -0,0 +1,64 @@
+<?php
+/*
+Super Get API
+-------------
+This script aggregates all GET-based PHP endpoints in the repository. It allows
+clients or Codex to discover available endpoints or execute one from a single
+entry point.
+
+Usage:
+- `GET /super_get_api.php` returns a JSON array of available endpoints.
+- `GET /super_get_api.php?search=term` returns endpoints whose path contains
+  the provided term.
+- `GET or POST /super_get_api.php?endpoint=PATH` runs the specified endpoint.
+  Additional parameters are forwarded directly to that script.
+
+Endpoints are discovered automatically under the `ITC` and `scalable_apis`
+folders. Only files beginning with `get` and ending in `.php` are considered.
+*/
+
+// Directories to scan for GET API scripts
+$allowedDirs = ['ITC', 'scalable_apis'];
+$baseDir = __DIR__;
+
+$endpoints = [];
+foreach ($allowedDirs as $dir) {
+    $iterator = new RecursiveIteratorIterator(
+        new RecursiveDirectoryIterator($baseDir . '/' . $dir, RecursiveDirectoryIterator::SKIP_DOTS)
+    );
+    foreach ($iterator as $file) {
+        if ($file->isFile() && preg_match('/^get.*\.php$/', $file->getFilename())) {
+            $relativePath = substr($file->getPathname(), strlen($baseDir) + 1);
+            $endpoints[$relativePath] = $relativePath;
+        }
+    }
+}
+
+// Handle search queries
+if (isset($_GET['search'])) {
+    $search = strtolower($_GET['search']);
+    $matches = array_values(array_filter($endpoints, function ($path) use ($search) {
+        return strpos(strtolower($path), $search) !== false;
+    }));
+    header('Content-Type: application/json');
+    echo json_encode($matches);
+    exit;
+}
+
+// List all endpoints if none specified
+if (!isset($_GET['endpoint'])) {
+    header('Content-Type: application/json');
+    echo json_encode(array_values($endpoints));
+    exit;
+}
+
+$endpoint = $_GET['endpoint'];
+if (!isset($endpoints[$endpoint])) {
+    http_response_code(404);
+    header('Content-Type: application/json');
+    echo json_encode(['status' => 'error', 'message' => 'Endpoint not found']);
+    exit;
+}
+
+include __DIR__ . '/' . $endpoints[$endpoint];
+?>
 
EOF
)
