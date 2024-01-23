<!DOCTYPE html>
<html>
<head><title>Error</title>
<meta name="viewport" content="width=device-width,initial-scale=1" />
<meta name="robots" content="noindex,nofollow,nosnippet,noarchive,nocache,noimageindex" />
<style <?= \Bang\Core::nonceTag(false) ?>>
html { height: 100vh; }
body {
	background: #000;
	color: #fff;
	font-family: monospace;
	min-height: 100vh;
	min-width: 300px;
	overflow: hidden;
}

article h1,
article h2 {
	margin: 1rem auto;
	text-align: center;
}
body,
div {
	box-sizing: border-box;
}
.hal9000,
.hal9000 div {
	border-radius: 50%;
}
.hal9000:before,
.hal9000:after,
.hal9000 .lens,
.hal9000 .animation {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background-repeat: no-repeat;
}

div::before,
div::after {
	border-radius: inherit;
	box-sizing: inherit;
	content: ' ';
	position: absolute;
	width: 100%;
	height: 100%;
}

.hal9000 {
	background-image:
		linear-gradient(
			45deg,
			#fefefe 10%,
			#5d6d94,
			#050718,
			#5d6d94,
			#fefefe 90%
		);
	height: 300px;
	width: 300px;
	padding: 10px;
	z-index: 10;
	margin: 0 auto;
	position: relative;
}
.hal9000::before {
	background-image:
		linear-gradient(
			#d9dee5,
			#151531
		),
		linear-gradient(
			90deg,
			#434c77,
			#0b0a1f,
			#434c77
		);
	background-blend-mode: hard-light, normal;
	background-position: 0 0;
	box-shadow: inset 0 0 14px 9px rgba(5, 7, 24, 0.4);
}
.hal9000::after {
	background-image:
		radial-gradient(
			#b10000 10%,
			rgba(177, 0, 0, 0) 71%
		);
	mix-blend-mode: lighten;
	background-position: -5px -10px;
	animation: redspot 5s linear infinite forwards;
}

.lens {
	background-image:
		radial-gradient(
			#b10000 12%,
			#120619 67%,
			#200517
		);
	border: 5px solid #050718;
	box-shadow: inset 0 0 0 10px #380014;
	z-index: 10;
	margin: 10px;
}
.lens::before {
	background-image:
		radial-gradient(
			#f00 20%,
			#470010 50%,
			#1a193e 80%
		);
	mix-blend-mode: soft-light;
	opacity: 0.8;
	z-index: 100;
}
.lens::after {
	background-image:
		radial-gradient(
			#fff 2px,
			#fff300 8px,
			rgba(255, 0, 0, .9) 14px,
			rgba(255, 0, 0, .08) 35px,
			transparent 35px)
		;
	z-index: 100;
	top: 0;
}

.reflections,
.reflections::before,
.reflections::after {
	background-image:
		radial-gradient(
			transparent 19%,
			#ec32aa 23%,
			#d4f6fc 28%,
			#ec32aa 33%,
			transparent 36%,
			transparent 38%,
			#e558d0 40%,
			#d0fcfe 45%,
			#ce73df 50%,
			transparent 52%,
			transparent 56%,
			#b883e7 60%,
			#b7ffff 65%,
			#3564c7 72%,
			transparent
		);
	background-size: 182px 182px;
	background-position: top center;
	border-radius: 15px 15px 5px 5px / 5px 5px 15px 15px;
	filter: blur(4px);
	position: relative;
	top: 26px;
	width: 58px;
	height: 75px;
	z-index: 10;
	margin: 0 auto;
}
.reflections {
	transform: perspective(30px) rotate3d(1, 0, 0, -15deg);
	transform-origin: top;
}
.reflections::before,
.reflections::after {
	height: 45px;
	top: 28px;
	position: absolute;
}
.reflections::before {
	left: -65px;
	transform: rotate(-43deg);
}
.reflections::after {
	right: -65px;
	transform: rotate(43deg);
}
.animation {
	animation: pulse 3s ease-in-out infinite;
	background: radial-gradient(rgba(255,0,0,.8), rgba(255,0,0,.2), rgba(255,0,0,.1));
	mix-blend-mode: color-dodge;
	opacity: 0;
	position: absolute;
	z-index: 1000;
}
@keyframes redspot {
	0% {
		background-position: -20px -75px;
	}
	25% {
		background-position: 75px -35px;
	}
	50% {
		background-position: 90px 40px;
	}
	75% {
		background-position: -80px 80px;
	}
	100% {
		background-position: -20px -75px;
	}
}

@keyframes pulse {
	0% {
		opacity: 0;
	}
	20% {
		opacity: 1;
	}
	80% {
		opacity: 0;
	}
}
</style>
</head>
<body>
<article>
	<h1><?= $code ?>/<?= $message ?></h1>
	<div class="hal9000">
		<div class="lens">
			<div class="reflections"></div>
		</div>
		<div class="animation"></div>
	</div>
	<h2>I'm sorry Dave,<br /> I'm afraid I can't do that..</h2>
</article>
</body>
</html>
