import * as React from 'react';
import {
	useReducer,
	createContext,
	useContext,
	useCallback,
	useRef,
	useState,
	ChangeEvent,
	FormEvent,
	MouseEvent,
	HTMLAttributes
} from 'react';

import { renderReactPage } from './renderReactPage';
import { postJson } from './postJson';
import { OneVisibleChild } from './containers';

import './group_edit.scss';

type CapabilityId = number;

interface IGroup
{
	id: number;
	groupname: string;
	capabilities: CapabilityId[];
}

interface ICapability
{
	id: number;
	app: string;
	name: string;
	description: string;
}

interface IPageModel
{
	group: IGroup;
	capabilities: { [appKey: string]: ICapability[] };
}

interface IEditState
{
	group: IGroup;
	savedGroup: IGroup;
	isSaving: boolean;
}

const GroupDispatchContext = createContext(null);

interface ISetGroupnameAction
{
	type: 'setGroupname';
	value: string;
}

interface IBeginSaveAction
{
	type: 'beginSave';
}

interface ISaveResponse
{
	error?: string|null;
}

interface IEndSaveAction
{
	type: 'endSave';
	response: ISaveResponse;
}

interface IAddCapabilityAction
{
	type: 'addCapability';
	capId: CapabilityId;
}

interface IRemoveCapabilityAction
{
	type: 'removeCapability';
	capId: CapabilityId;
}

type EditAction = ISetGroupnameAction
	| IBeginSaveAction
	| IEndSaveAction
	| IAddCapabilityAction
	| IRemoveCapabilityAction
;

function reducer(state: IEditState, action: EditAction)
{
	const group = {...state.group};
	let savedGroup = { ...state.savedGroup };
	let isSaving = state.isSaving;
	const caps = new Set(group.capabilities);

	if (action.type === 'setGroupname')
	{
		group.groupname = action.value;
	}
	else if (action.type === 'beginSave')
	{
		isSaving = true;
	}
	else if (action.type === 'endSave')
	{
		const { response } = action;
		if (response.error)
		{
			console.error(response.error);
		}
		else
		{
			savedGroup = {...group};
		}

		isSaving = false;
	}
	else if (action.type === 'addCapability')
	{
		caps.add(action.capId);
	}
	else if (action.type === 'removeCapability')
	{
		caps.delete(action.capId);
	}

	group.capabilities = Array.from(caps);
	return { group, savedGroup, isSaving };
}

function groupsAreEqual(a: IGroup, b: IGroup): boolean
{
	if (a.groupname !== b.groupname)
		return false;

	if (a.capabilities.length !== b.capabilities.length)
		return false;

	for (const capId of a.capabilities)
	{
		if (!b.capabilities.includes(capId))
			return false;
	}

	return true;
}

interface ICapabilitiesProps
{
	groupCapabilities: CapabilityId[];
	allCapabilities: { [appKey: string]: ICapability[] };
}

function Button(props: HTMLAttributes<HTMLButtonElement>)
{
	const { onClick } = props;
	const btn = useRef<HTMLButtonElement>(null);

	const wrapOnClick = useCallback((e: MouseEvent<HTMLButtonElement>) => {
		if (e.target === btn.current)
		{
			e.preventDefault(); // no form submission
			onClick && onClick(e);
		}
	}, [onClick, btn.current]);

	const copyProps = {...props};
	copyProps.onClick = wrapOnClick;

	return <button ref={btn} {...copyProps} />;
}

function Capabilities(props: ICapabilitiesProps)
{
	const { groupCapabilities, allCapabilities } = props;

	const apps = Object.keys(allCapabilities);
	if (apps.length < 1)
		throw new Error('No apps. Something is wrong');

	const firstApp = apps[0];
	const [currentApp, setCurrentApp] = useState(firstApp);

	const appCaps = allCapabilities[currentApp];
	if (appCaps.length < 1)
		throw new Error('App has no capabilities. Something is wrong.');

	const firstCap = appCaps[0];
	const [currentCap, setCurrentCap] = useState(firstCap);

	const dispatch = useContext(GroupDispatchContext);

	const changeCap = (capId: CapabilityId, e: ChangeEvent<HTMLInputElement>) => {
		e.stopPropagation();
		if (e.target.checked) {
			dispatch({ type: 'addCapability', capId });
		} else {
			dispatch({ type: 'removeCapability', capId });
		}
	};

	const hasCurrentCap = groupCapabilities.includes(currentCap?.id);

	const appOptions = apps.map((appKey: string) => {
		return <option key={appKey} value={appKey}> {appKey} </option>;
	});

	const appSelect = <select onChange={(e: ChangeEvent<HTMLSelectElement>) => setCurrentApp(e.target.value)}>
		{appOptions}
	</select>;

	const capSelects = [];
	for (const app of apps)
	{
		const caps = allCapabilities[app];
		const capOptions = caps.map((cap: ICapability) => {
			return <option key={cap.id} value={cap.id}> {cap.name} </option>;
		});

		capSelects.push(<label
				key={app}
				className={app === currentApp ? 'ovc-visible' : ''}
			> Capability: <select
			onChange={(e: ChangeEvent<HTMLSelectElement>) => {
				setCurrentCap(caps[e.target.selectedIndex]);
			}}
		> {capOptions} </select> </label>);
	}

	const capButtons = appCaps.map((cap: ICapability) => {
		const hasCap = groupCapabilities.includes(cap.id);
		let className = 'cap';
		if (cap.id === currentCap.id)
			className += ' selected';

		return <Button className={className}
			key={cap.id}
			tabIndex={0}
			onClick={() => setCurrentCap(cap)}
		>
			<input type="checkbox"
				checked={hasCap}
				onChange={changeCap.bind(null, cap.id)}
			/>
			{cap.name}
		</Button>;
	});

	const details = [];
	for (const app of apps)
	{
		const caps = allCapabilities[app];
		for (const cap of caps)
		{
			let className = 'cap-details';
			if (currentApp === app && currentCap.id === cap.id)
				className += ' ovc-visible';

			details.push(<div key={cap.id} className={className}>
				<p>
					<label>
						<input
							type="checkbox"
							checked={groupCapabilities.includes(cap.id)}
							onChange={changeCap.bind(null, cap.id)}
						/> enabled
					</label>
				</p>
				<em> {cap.description} </em>
			</div>);
		}
	}

	return <section className="section">
		<h3> Capabilities </h3>
		<div className="cap-edit">
		<div className="cap-select">
			<label> Application: {appSelect} </label>
			<OneVisibleChild> {capSelects} </OneVisibleChild>
		</div>
		<OneVisibleChild className="cap-details-panel">
			{details}
		</OneVisibleChild>
		</div>
	</section>;
}

interface IGroupNameProps
{
	groupname: string;
}

function GroupName(props: IGroupNameProps)
{
	const { groupname } = props;

	const dispatch = useContext(GroupDispatchContext);

	const onChangeGroupname = useCallback((e: ChangeEvent<HTMLInputElement>) => {
		dispatch({ type: 'setGroupname', value: e.target.value });
	}, []);

	return <label> groupname:
		<input
			type="text"
			value={groupname}
			onChange={onChangeGroupname}
			/>
	</label>;
}

interface IGroupPropertiesProps
{
	groupname: string;
}

function GroupProperties(props: IGroupPropertiesProps)
{
	const { groupname } = props;

	return <section className="section">
		<h3> Group properties </h3>
		<GroupName groupname={groupname} />
	</section>;
}

function Page(props: IPageModel)
{
	const initialState = {
		group: props.group,
		savedGroup: props.group,
		isSaving: false
	};

	const id = props.group.id;

	const [state, dispatch] = useReducer(reducer, initialState);

	const { isSaving, group, savedGroup } = state;

	const hasChange = !groupsAreEqual(group, savedGroup);

	const onSave = useCallback(async (e: FormEvent) => {
		e.preventDefault(); // we'll send our own request
		dispatch({ type: 'beginSave' });

		const response = await postJson<ISaveResponse>('./save', { body: group });

		dispatch({ type: 'endSave', response });
	}, [id, group]);

	return <form onSubmit={onSave}>
		<GroupDispatchContext.Provider value={dispatch}>
			<h1> Edit group </h1>

			<div className="section-container">
				<GroupProperties groupname={group.groupname} />

				<Capabilities
					allCapabilities={props.capabilities}
					groupCapabilities={group.capabilities}
				/>

			</div>

			<button disabled={isSaving || !hasChange} > Save </button>
		</GroupDispatchContext.Provider>
	</form>;
}

renderReactPage<IPageModel>(model => <Page {...model} />);
