<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>


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

    <!-- mycss -->
    <link rel="stylesheet" href="dist/mycss/loadingscreen.css">
    <link rel="stylesheet" href="dist/mycss/loginandforgotpassword.css">
    <!-- Gsap -->
    <script src="gsap-public/minified/gsap.min.js"></script>

    <script data-cfasync="false" nonce="a28e568f-c174-4d59-9b55-df26fed5f8b7">
        try {
            (function (w, d) {
                ! function (j, k, l, m) {
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
                        j.zaraz._f = function (n) {
                            return async function () {
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
                            q.parentNode.insertBefore(r, q)
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

    </div>
    <!-- ... existing code ... -->
    <style>
        .profile-user-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            margin-bottom: 1.5rem;
            border: 4px solid #fff;
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.2);
            transition: all 0.3s ease;
        }

        .profile-user-img:hover {
            transform: scale(1.05);
        }

        .nav-pills .nav-link {
            border-radius: 30px;
            padding: 0.6rem 1.2rem;
            margin-right: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-pills .nav-link.active {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
        }

        .nav-pills .nav-link:not(.active):hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
        }

        .custom-switch {
            padding-left: 2.25rem;
        }

        .card {
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            border-radius: 15px;
            border: none;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }

        .card-primary.card-outline {
            border-top: 3px solid #007bff;
        }

        .card-header {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1rem 1.5rem;
        }

        .btn {
            border-radius: 30px;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
        }

        .btn-sm {
            padding: 0.4rem 1rem;
        }

        .form-control {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 0.8rem 1.2rem;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .15);
            background-color: #fff;
        }

        .form-control:hover {
            background-color: #fff;
        }

        .badge {
            padding: 6px 12px;
            font-size: 0.85rem;
            font-weight: 500;
            border-radius: 30px;
        }

        .list-group-item {
            border-left: none;
            border-right: none;
            padding: 1rem 1.25rem;
            transition: all 0.3s ease;
        }

        .list-group-item:hover {
            background-color: #f8f9fa;
        }

        .list-group-item:first-child {
            border-top: none;
        }

        .list-group-item b {
            font-weight: 600;
            color: #495057;
        }

        .list-group-item a.float-right {
            font-weight: 600;
            color: #007bff;
        }

        .profile-username {
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #343a40;
        }

        .text-muted {
            color: #6c757d !important;
            font-weight: 500;
        }

        label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .content-wrapper {
            background-color: #f8f9fa;
        }

        .content-header h1 {
            font-weight: 700;
            color: #343a40;
        }

        .breadcrumb {
            background-color: transparent;
            padding: 0;
        }

        .breadcrumb-item a {
            color: #007bff;
            font-weight: 500;
        }

        .breadcrumb-item.active {
            color: #6c757d;
            font-weight: 500;
        }

        /* Animation for save button */
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.4);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(0, 123, 255, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(0, 123, 255, 0);
            }
        }

        .btn-primary:focus {
            animation: pulse 1.5s infinite;
        }

        /* Camera button enhancement */
        .btn-sm.btn-primary.position-absolute {
            bottom: 5px;
            right: 35%;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, #007bff, #0056b3);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .btn-sm.btn-primary.position-absolute:hover {
            transform: scale(1.1);
        }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">