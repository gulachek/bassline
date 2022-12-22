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

/* Element structure
 * <flex-row>
 * 	<menu>
 * 		<li><button> Item 1 </button></li> <!-- selected -->
 * 		<li><button> Item 2 </button></li>
 * 	</menu>
 * 	<tab-item title="Item 1" display="block"> ... </tab-item>
 * 	<tab-item title="Item 2" display="none"> ... </tab-item>
 * </flex-row>
 */

class NavigationTab extends HTMLElement
{
	mut;
	tabList;
	panels;
	tabs;
	itemCounter;
	selectedItemId;
	tabPanelContainer;

	constructor()
	{
		super();

		this.mut = new MutationObserver(this.onMutation.bind(this));
		this.itemCounter = 0;
		this.panels = new Map();
		this.tabs = new Map();
		this.selectedItemId = -1;
	}

	connectedCallback()
	{
		const shadow = this.attachShadow({mode:'open'});
		const styleLink = document.createElement('link');

		// absolute path is a bit unfortunate
		styleLink.setAttribute('rel', 'stylesheet');
		styleLink.setAttribute('href', '/static/nav_tab.css');
		shadow.appendChild(styleLink);

		const fixture = document.createElement('div');
		fixture.classList.add('fixture');
		shadow.appendChild(fixture);

		const tabList = this.tabList = document.createElement('div');
		tabList.classList.add('tab-list');
		fixture.appendChild(tabList);

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

	onMutation(records)
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

	addTabItem(tabItem)
	{
		if (!(tabItem instanceof TabItem))
			throw new Error('nav-tab only supports tab-item children');

		const itemId = this.itemCounter++;

		const tab = document.createElement('button');
		tab.classList.add('tab');
		tab.innerText = tabItem.title;
		tab.addEventListener('click', this.selectTabItem.bind(this, itemId));
		this.tabList.appendChild(tab);

		const tabPanel = document.createElement('div');
		tabPanel.classList.add('tab-panel');
		const slot = document.createElement('slot');
		tabPanel.appendChild(slot);
		slot.setAttribute('name', tabItem.title);
		tabItem.setAttribute('slot', tabItem.title);
		this.tabPanelContainer.appendChild(tabPanel);

		this.panels.set(itemId, tabPanel);
		this.tabs.set(itemId, tab);

		if (!this.selected)
			this.selectTabItem(itemId);
	}

	get selected()
	{
		if (this.selectedItemId === -1)
			return null;

		return this.panels.get(this.selectedItemId);
	}

	get selectedMenu()
	{
		if (this.selectedItemId === -1)
			return null;

		return this.tabs.get(this.selectedItemId);
	}

	selectTabItem(itemId)
	{
		const current = this.selected;
		if (current)
		{
			current.classList.remove('selected');
			this.selectedMenu.classList.remove('selected');
		}

		this.panels.get(itemId).classList.add('selected');
		this.tabs.get(itemId).classList.add('selected');
		this.selectedItemId = itemId;
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
}

customElements.define('tab-item', TabItem);
