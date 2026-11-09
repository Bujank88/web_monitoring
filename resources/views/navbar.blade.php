<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
    </ul>


    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <a class="nav-brand" href="#">
                <img src="{{ asset('images/TRACERS_2.png') }}" alt="Logo" style="height: 50px; margin-top: -10px;">
            </a>
        </li>
    </ul>

</nav>

<style>
    .scrolling-text {
        overflow: hidden;
        white-space: nowrap;
        position: absolute;
        left: 70%;
        transform: translateX(-50%);
        width: 500px;
        /* Sesuaikan lebar */
    }

    .scrolling-text span {
        display: inline-block;
        padding-left: 20%;
        animation: marquee 10s linear infinite;
    }

    @keyframes marquee {
        from {
            transform: translateX(100%);
        }

        to {
            transform: translateX(-100%);
        }
    }
</style>