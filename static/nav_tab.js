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
	tabPanelContainer;
	dropdown;

	panels;
	tabs;
	cachedSelection;

	constructor()
	{
		super();

		this.mut = new MutationObserver(this.onMutation.bind(this));
		this.panels = [];
		this.tabs = [];
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

		const tabListContainer = document.createElement('div');
		tabListContainer.classList.add('tab-list-container');
		fixture.appendChild(tabListContainer);

		const tabList = this.tabList = document.createElement('div');
		tabList.classList.add('tab-list');
		tabListContainer.appendChild(tabList);

		const dropdown = this.dropdown = document.createElement('select');
		dropdown.classList.add('dropdown');
		this.dropdown.addEventListener('change', this.onSelect.bind(this));
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

		const index = this.tabs.length;

		const option = document.createElement('option');
		option.value = index;
		option.innerText = tabItem.title;
		this.dropdown.appendChild(option);

		const tab = document.createElement('button');
		tab.classList.add('tab');
		tab.innerText = tabItem.title;
		tab.addEventListener('click', this.onClick.bind(this, index));
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

	onSelect()
	{
		this.redraw();
	}

	onClick(index)
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
			panel.classList.remove('selected');
		}

		const current = this.selected;
		if (current)
		{
			const { tab, panel } = this.cachedSelection = current;
			tab.classList.add('selected');
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
}

customElements.define('tab-item', TabItem);
