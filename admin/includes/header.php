<?php
// Include authentication check
require_once __DIR__ . '/auth_check.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>


    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet" href="../plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <!-- iCheck -->
    <link rel="stylesheet" href="../plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- JQVMap -->
    <link rel="stylesheet" href="../plugins/jqvmap/jqvmap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="../dist/css/adminlte.min.css?v=3.2.0">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="../plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="../plugins/daterangepicker/daterangepicker.css">
    <!-- summernote -->
    <link rel="stylesheet" href="../plugins/summernote/summernote-bs4.min.css">

    <!--ASSETS/Custom css-->
    <link rel="stylesheet" href="assets/css/profile-settings/profile-settings.css">
    <link rel="stylesheet" href="assets/css/add-company/add-company.css">
    <!--ASSETS/COMPONENTS css-->
    <link rel="stylesheet" href="assets/components/drag-and-drop-image/drag-and-drop-image.css">
    <link rel="stylesheet" href="assets/components/table/table.css">

    <!-- Custom CSS for user management -->
    <link rel="stylesheet" href="assets/css/user-management.css">
    <link rel="stylesheet" href="assets/css/custom.css">

    <!-- Select2 -->
    <link rel="stylesheet" href="../plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="../plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">

    <!-- Optional: Create placeholder CSS files to prevent 404 errors -->
    <style>
        /* Inline CSS to replace missing files */
        /* This helps prevent 404 errors */

        /* Select2 custom styles */
        .select2-container--bootstrap4 {
            width: 100% !important;
        }

        .select2-container--bootstrap4 .select2-selection {
            height: calc(2.25rem + 2px) !important;
        }

        .select2-container--bootstrap4 .select2-selection--single {
            padding: .375rem .75rem;
        }

        .select2-container--bootstrap4 .select2-results__option {
            padding: .375rem .75rem;
        }

        .select2-container--bootstrap4 .select2-search--dropdown .select2-search__field {
            padding: .375rem .75rem;
            border: 1px solid #ced4da;
            border-radius: .25rem;
        }

        .select2-container--bootstrap4 .select2-results__options {
            max-height: 200px;
            overflow-y: auto;
        }

        .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            line-height: 1.5;
        }
    </style>

    <script data-cfasync="false" nonce="a28e568f-c174-4d59-9b55-df26fed5f8b7">
        try {
            (function(w, d) {
                ! function(j, k, l, m) {
                    if (j.zaraz) console.error("zaraz is loaded twice");
                    else {
                        j[l] = j[l] || {};
                        j[l].executed = [];
                        j.zaraz = {
                            deferred: [],
                            listeners: []
                        };
                        j.zaraz._v = "5850";
                        j.zaraz._n = "a28e568f-c174-4d59-9b55-df26fed5f8b7";
                        j.zaraz.q = [];
                        j.zaraz._f = function(n) {
                            return async function() {
                                var o = Array.prototype.slice.call(arguments);
                                j.zaraz.q.push({
                                    m: n,
                                    a: o
                                })
                            }
                        };
                        for (const p of ["track", "set", "debug"]) j.zaraz[p] = j.zaraz._f(p);
                        j.zaraz.init = () => {
                            var q = k.getElementsByTagName(m)[0],
                                r = k.createElement(m),
                                s = k.getElementsByTagName("title")[0];
                            s && (j[l].t = k.getElementsByTagName("title")[0].text);
                            j[l].x = Math.random();
                            j[l].w = j.screen.width;
                            j[l].h = j.screen.height;
                            j[l].j = j.innerHeight;
                            j[l].e = j.innerWidth;
                            j[l].l = j.location.href;
                            j[l].r = k.referrer;
                            j[l].k = j.screen.colorDepth;
                            j[l].n = k.characterSet;
                            j[l].o = (new Date).getTimezoneOffset();
                            if (j.dataLayer)
                                for (const t of Object.entries(Object.entries(dataLayer).reduce(((u, v) => ({
                                        ...u[1],
                                        ...v[1]
                                    })), {}))) zaraz.set(t[0], t[1], {
                                    scope: "page"
                                });
                            j[l].q = [];
                            for (; j.zaraz.q.length;) {
                                const w = j.zaraz.q.shift();
                                j[l].q.push(w)
                            }
                            r.defer = !0;
                            for (const x of [localStorage, sessionStorage]) Object.keys(x || {}).filter((z => z.startsWith("_zaraz_"))).forEach((y => {
                                try {
                                    j[l]["z_" + y.slice(7)] = JSON.parse(x.getItem(y))
                                } catch {
                                    j[l]["z_" + y.slice(7)] = x.getItem(y)
                                }
                            }));
                            r.referrerPolicy = "origin";
                            r.src = "/cdn-cgi/zaraz/s.js?z=" + btoa(encodeURIComponent(JSON.stringify(j[l])));
                            // q.parentNode.insertBefore(r, q)
                        };
                        ["complete", "interactive"].includes(k.readyState) ? zaraz.init() : j.addEventListener("DOMContentLoaded", zaraz.init)
                    }
                }(w, d, "zarazData", "script");
                window.zaraz._p = async bs => new Promise((bt => {
                    if (bs) {
                        bs.e && bs.e.forEach((bu => {
                            try {
                                const bv = d.querySelector("script[nonce]"),
                                    bw = bv?.nonce || bv?.getAttribute("nonce"),
                                    bx = d.createElement("script");
                                bw && (bx.nonce = bw);
                                bx.innerHTML = bu;
                                bx.onload = () => {
                                    d.head.removeChild(bx)
                                };
                                d.head.appendChild(bx)
                            } catch (by) {
                                console.error(`Error executing script: ${bu}\n`, by)
                            }
                        }));
                        Promise.allSettled((bs.f || []).map((bz => fetch(bz[0], bz[1]))))
                    }
                    bt()
                }));
                zaraz._p({
                    "e": ["(function(w,d){})(window,document)"]
                });
            })(window, document)
        } catch (e) {
            throw fetch("/cdn-cgi/zaraz/t"), e;
        };
    </script>

    <!-- AdminLTE - Fix paths -->
    <script src="../dist/js/adminlte.min.js"></script>

    <!-- BS-Custom-File-Input - Fix paths -->
    <script src="../plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>

</head>

<body class="hold-transition sidebar-mini layout-fixed">