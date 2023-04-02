export class LoadingOverlay extends HTMLElement
{
	private _handler: (e: Event) => any;

	public constructor()
	{
		super();
		this._handler = this.onLoad.bind(this);
	}

	public connectedCallback()
	{
		this.style.display = 'none';
		this.style.height = '100%';

		window.addEventListener('load', this._handler);
	}

	public disconnectedCallback()
	{
		window.removeEventListener('load', this._handler);
	}

	public onLoad(e: Event): void
	{
		this.style.display = 'block';
	}
}

customElements.define('loading-overlay', LoadingOverlay);
