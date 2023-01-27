import * as React from 'react';
import {
	useReducer,
	useCallback,
	useRef,
	ChangeEvent,
	FormEvent
} from 'react';

import { renderReactPage } from './renderReactPage';
import { postJson } from './postJson';

interface IGroup
{
	id: number;
	groupname: string;
}

interface IPageModel
{
	group: IGroup;
}

interface IEditState
{
	group: IGroup;
	savedGroup: IGroup;
	isSaving: boolean;
}

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

type EditAction = ISetGroupnameAction
	| IBeginSaveAction
	| IEndSaveAction
;

function reducer(state: IEditState, action: EditAction)
{
	const group = {...state.group};
	let savedGroup = { ...state.savedGroup };
	let isSaving = state.isSaving;

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

	return { group, savedGroup, isSaving };
}

function groupsAreEqual(a: IGroup, b: IGroup): boolean
{
	return a.groupname === b.groupname;
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

	return <form onSubmit={onSave}>
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

		<button disabled={isSaving || !hasChange} > Save </button>
	</form>;
}

renderReactPage<IPageModel>(model => <Page {...model} />);
