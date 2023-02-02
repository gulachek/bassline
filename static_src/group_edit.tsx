import * as React from 'react';
import {
	useReducer,
	createContext,
	useContext,
	useCallback,
	useRef,
	ChangeEvent,
	FormEvent
} from 'react';

import { renderReactPage } from './renderReactPage';
import { postJson } from './postJson';

type CapabilityId = number;

interface IGroup
{
	id: number;
	groupname: string;
	capabilities: CapabilityId[];
}

interface ICapability
{
	app: string;
	name: string;
	description: string;
}

interface IPageModel
{
	group: IGroup;
	capabilities: { [key: string]: ICapability };
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

interface ICapabilitySwitchProps
{
	capId: number;
	groupHasCap: boolean;
	capability: ICapability;
}

function CapabilitySwitch(props: ICapabilitySwitchProps)
{
	const { groupHasCap, capability, capId } = props;
	const { name, app, description } = capability;

	const dispatch = useContext(GroupDispatchContext);

	const changeCap = (e: ChangeEvent<HTMLInputElement>) => {
		if (e.target.checked) {
			dispatch({ type: 'addCapability', capId });
		} else {
			dispatch({ type: 'removeCapability', capId });
		}
	};

	return <div>
		<label>
		<input type="checkbox"
			checked={groupHasCap}
			onChange={changeCap}
			/> {app}.{name}
		</label>
		<em> {description} </em>
	</div>;
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

	const onChangeGroupname = useCallback((e: ChangeEvent<HTMLInputElement>) => {
		dispatch({ type: 'setGroupname', value: e.target.value });
	}, []);

	const onSave = useCallback(async (e: FormEvent) => {
		e.preventDefault(); // we'll send our own request
		dispatch({ type: 'beginSave' });
	}, [id, group]);

	// implement save
	const saveRequestInFlight = useRef(false);
	if (isSaving && !saveRequestInFlight.current)
	{
		saveRequestInFlight.current = true;
		const promise = postJson<ISaveResponse>('./save', { body: group });
		promise.then((response: ISaveResponse) =>	{
			saveRequestInFlight.current = false;
			dispatch({ type: 'endSave', response });
		});
	}

	const capabilities = [];
	for (const capIdStr in props.capabilities)
	{
		const capId = parseInt(capIdStr);
		const hasCap = group.capabilities.includes(capId);
		const cap = props.capabilities[capIdStr];
		capabilities.push(<CapabilitySwitch
			key={capId}
			capId={capId}
			groupHasCap={hasCap}
			capability={cap}
		/>);
	}

	return <form onSubmit={onSave}>
		<GroupDispatchContext.Provider value={dispatch}>
		<h1> Edit group </h1>
		<fieldset>
			<legend> Group properties </legend>
			<label> groupname:
				<input
					type="text"
					value={group.groupname}
					onChange={onChangeGroupname}
					/>
			</label>
			
		</fieldset>

		<fieldset>
			<legend> Capabilities </legend>
			{capabilities}
		</fieldset>

		<button disabled={isSaving || !hasChange} > Save </button>
		</GroupDispatchContext.Provider>
	</form>;
}

renderReactPage<IPageModel>(model => <Page {...model} />);
