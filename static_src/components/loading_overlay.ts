function onLoad()
{
	document.documentElement.classList.remove('loading');
}

document.documentElement.classList.add('loading');
window.addEventListener('load', onLoad);
