export class LoadingOverlay extends HTMLElement
{
	private _handler: (e: Event) => any;
	private _displayValue: string;

	public constructor()
	{
		super();
		this._handler = this.onLoad.bind(this);
		this._displayValue = 'initial';
	}

	public connectedCallback()
	{
		this._displayValue = window.getComputedStyle(this).display;
		this.style.display = 'none';
		window.addEventListener('load', this._handler);
	}

	public disconnectedCallback()
	{
		window.removeEventListener('load', this._handler);
	}

	public onLoad(e: Event): void
	{
		this.style.display = this._displayValue;
	}
}

customElements.define('loading-overlay', LoadingOverlay);
