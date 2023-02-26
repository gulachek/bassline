/* Element usage:
 * <nav-tab>
 * <tab-item title="Item 1">
 * 	<span> Content goes here </span>
 * </tab-item>
 * <tab-item title="Item 2">
 * 	<span> Content goes here </span>
 * 	More content for item 2
 * </tab-item>
 * </nav-tab>
 */

interface ISelection
{
	tab: HTMLButtonElement;
	panel: HTMLDivElement;
}

export class NavigationTab extends HTMLElement
{
	private mut: MutationObserver;
	private tabList: HTMLDivElement;
	private tabPanelContainer: HTMLDivElement;
	private dropdown: HTMLSelectElement;

	private panels: HTMLDivElement[];
	private tabs: HTMLButtonElement[];
	private keyToIndex: Map<string, number>;
	private cachedSelection: ISelection;

	constructor()
	{
		super();

		this.mut = new MutationObserver(this.onMutation.bind(this));
		this.panels = [];
		this.tabs = [];
		this.keyToIndex = new Map<string, number>();
	}

	connectedCallback()
	{
		const shadow = this.attachShadow({mode:'open'});
		const styleLink = document.createElement('link');

		// absolute path is a bit unfortunate
		styleLink.setAttribute('rel', 'stylesheet');
		styleLink.setAttribute('href', '/assets/tablist.css');
		shadow.appendChild(styleLink);

		const fixture = document.createElement('div');
		fixture.classList.add('fixture');
		shadow.appendChild(fixture);

		const tabListContainer = document.createElement('div');
		tabListContainer.classList.add('tab-list-container');
		fixture.appendChild(tabListContainer);

		const tabList = this.tabList = document.createElement('div');
		tabList.classList.add('tab-list');
		tabListContainer.appendChild(tabList);
		tabList.addEventListener('keydown', this.onTabListKeyDown.bind(this));

		const dropdown = this.dropdown = document.createElement('select');
		dropdown.classList.add('dropdown');
		dropdown.part.add('dropdown');
		dropdown.addEventListener('change', this.onSelect.bind(this));
		tabListContainer.appendChild(dropdown);

		// contains <slot />
		this.tabPanelContainer = document.createElement('div');
		this.tabPanelContainer.classList.add('tab-panel-container');
		fixture.appendChild(this.tabPanelContainer);

		this.mut.observe(this, { childList: true });
	}

	disconnectedCallback()
	{
		this.mut.disconnect();
	}

	onMutation(records: MutationRecord[])
	{
		for (const record of records)
		{
			for (let i = 0; i < record.addedNodes.length; ++i)
			{
				const node = record.addedNodes[i];
				if (node.nodeType !== Node.ELEMENT_NODE)
					continue;

				this.addTabItem(node);
			}
		}
	}

	activateTab(key: string)
	{
		if (!this.keyToIndex.has(key))
			throw new Error(`Invalid tab key ${key}`);

		this.onClick(this.keyToIndex.get(key));
	}

	addTabItem(tabItem: Node)
	{
		if (!(tabItem instanceof TabItem))
			throw new Error('nav-tab only supports tab-item children');

		const index = this.tabs.length;
		this.keyToIndex.set(tabItem.key, index);

		const option = document.createElement('option');
		option.value = index.toString();
		option.innerText = tabItem.title;
		this.dropdown.appendChild(option);

		const tab = document.createElement('button');
		tab.tabIndex = -1;
		tab.classList.add('tab');
		tab.innerText = tabItem.title;
		tab.addEventListener('click', this.onClick.bind(this, index));
		tab.part.add('tab');
		this.tabList.appendChild(tab);

		const tabPanel = document.createElement('div');
		tabPanel.classList.add('tab-panel');
		const slot = document.createElement('slot');
		const slotName = `tab-${index}`;
		tabPanel.appendChild(slot);
		slot.setAttribute('name', slotName);
		tabItem.setAttribute('slot', slotName);
		this.tabPanelContainer.appendChild(tabPanel);

		this.panels.push(tabPanel);
		this.tabs.push(tab);

		this.redraw();
	}

	onTabListKeyDown(e: KeyboardEvent)
	{
		const t = e.target as HTMLElement;
		const p = t.parentElement;

		if (e.key === 'ArrowLeft')
		{
			if (t.previousElementSibling)
				(t.previousElementSibling as HTMLElement).focus();
			else if (p.lastElementChild !== t)
				(p.lastElementChild as HTMLElement).focus();
		}
		else if (e.key === 'ArrowRight')
		{
			if (t.nextElementSibling)
				(t.nextElementSibling as HTMLElement).focus();
			else if (p.firstElementChild !== t)
				(p.firstElementChild as HTMLElement).focus();
		}
	}

	onSelect()
	{
		this.redraw();
	}

	onClick(index: number)
	{
		this.dropdown.selectedIndex = index;
		this.redraw();
	}

	get selectedIndex()
	{
		return this.dropdown.selectedIndex;
	}

	get selected()
	{
		const index = this.selectedIndex;

		if (index === -1)
			return null;

		return {
			tab: this.tabs[index],
			panel: this.panels[index]
		};
	}

	// source of truth is 1) new state is dropdown selection 2) old state is cachedSelection
	redraw()
	{
		if (this.cachedSelection)
		{
			const { tab, panel } = this.cachedSelection;
			delete this.cachedSelection;
			tab.classList.remove('selected');
			tab.tabIndex = -1;
			tab.part.remove('selected-tab');
			panel.classList.remove('selected');
		}

		const current = this.selected;
		if (current)
		{
			const { tab, panel } = this.cachedSelection = current;
			tab.classList.add('selected');
			tab.tabIndex = 0;
			tab.part.add('selected-tab');
			panel.classList.add('selected');
		}
	}
}

customElements.define('nav-tab', NavigationTab);

class TabItem extends HTMLElement
{
	constructor()
	{
		super();
	}

	connectedCallback()
	{
	}

	get title()
	{
		return this.getAttribute('title');
	}

	get key()
	{
		return this.getAttribute('key');
	}
}

customElements.define('tab-item', TabItem);
