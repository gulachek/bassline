class LoadingOverlay extends HTMLElement
{
	_handler;
	_displayValue;

	constructor()
	{
		super();
		this._handler = this.onLoad.bind(this);
	}

	connectedCallback()
	{
		this._displayValue = window.getComputedStyle(this).display;
		this.style.display = 'none';
		window.addEventListener('load', this._handler);
	}

	disconnectedCallback()
	{
		window.removeEventListener('load', this._handler);
	}

	onLoad()
	{
		this.style.display = this._displayValue;
	}
}

customElements.define('loading-overlay', LoadingOverlay);
