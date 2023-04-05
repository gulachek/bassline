import * as React from 'react';
import {
	useRef,
	useMemo,
	useEffect,
	useCallback,
	useState,
	useReducer,
	useContext,
	createContext,
	useImperativeHandle,
	FormEvent,
	ChangeEvent,
	FC,
	ReactNode,
	MutableRefObject
} from 'react';

import { useElemState } from './useElemState';
import { renderReactPage } from './renderReactPage';
import { postJson } from './postJson';
import { requireAsync } from './requireAsync';
import { AuthPluginUserEditComponent } from './authPluginUserEdit';
import { AutoSaveForm } from './autosave/AutoSaveForm';
import { SaveIndicator } from './autosave/SaveIndicator';

import './user_edit.scss';

interface ISaveReponse
{
	errorMsg?: string|null;
}

const UserDispatchContext = createContext(null);

type PluginDataEqualFn = (data: any, savedData: any) => boolean;

interface IAuthPluginScriptModule
{
	UserEditor: AuthPluginUserEditComponent;
	modelEquals: PluginDataEqualFn;
}

function useModule<T>(script: string)
{
	const [mod, setMod] = useState(null);
	useMemo(async () => {
		setMod(await requireAsync<T>(script));
	}, [script]);

	return mod;
}

interface IAuthPluginData
{
	key: string;
	script: string;
	title: string;
	data: any;
}

function ModalErrorMsg(props: {msg: string|null})
{
	const dialogRef = useRef<HTMLDialogElement>();
	const { msg } = props;

	useEffect(() => {

		if (msg)
			dialogRef.current.showModal();
		else
			dialogRef.current.close();

	}, [dialogRef.current, msg]);

	return <dialog ref={dialogRef}>
		<h2> Error </h2>
		<p> {msg} </p>
		<form method="dialog">
			<button> Ok </button>
		</form>
	</dialog>;
}

interface IUser
{
	id: number;
	username: string;
	is_superuser: boolean;
	groups: number[];
	primary_group: number;
}

function userEquals(left: IUser, right: IUser)
{
	if (left.id !== right.id)
		return false;

	if (left.username !== right.username)
		return false;

	if (left.primary_group !== right.primary_group)
		return false;

	if (left.groups.length !== right.groups.length)
		return false;

	const rightSet = new Set(right.groups);
	for (const gid of left.groups)
		if (!rightSet.has(gid))
			return false;

	return true;
}

interface IPatterns
{
	username: string;
}

interface IFormData
{
	user: IUser;
	pluginData: { [key: string]: any };
}

interface IGroup
{
	id: number;
	groupname: string;
}

type Groups = { [id: string]: IGroup };

interface IGroupMembershipProps
{
	groupMembership: number[];
	allGroups: Groups;
	primaryId: number;
}

function GroupMembership(props: IGroupMembershipProps)
{
	const { groupMembership, allGroups, primaryId } = props;
	const groupIds = Object.keys(allGroups);

	const dispatch = useContext(UserDispatchContext);

	const switches = groupIds.map((gid) => {
		const { groupname, id } = allGroups[gid];
		const inGroup = groupMembership.includes(id);
		const onCheck = (e: ChangeEvent<HTMLInputElement>) => {
			dispatch({ type: 'joinGroup', groupId: id, inGroup: e.target.checked });
		};

		return <div key={gid}>
			<label>
				<input type="checkbox"
					data-groupname={groupname}
					checked={inGroup}
					onChange={onCheck}
					disabled={id === primaryId}
				/>
				{groupname}
			</label>
		</div>;
	});

	return <section className="section">
		<h3> Group Membership </h3>
		{switches}
	</section>;
}

interface IPageState
{
	data: IFormData;
	savedData: IFormData;
}

interface IPageSetUsernameAction
{
	type: 'setUsername';
	username: string;
}

interface IPageJoinGroupAction
{
	type: 'joinGroup';
	groupId: number;
	inGroup: boolean;
}

interface IPageChangePrimaryGroupAction
{
	type: 'changePrimaryGroup';
	groupId: number;
}

interface IPageSetPluginDataAction
{
	type: 'setPluginData';
	key: string;
	data: any;
}

interface IPageUpdateSavedDataAction
{
	type: 'updateSavedData';
	savedData: IFormData;
}

type PageAction =
	IPageSetUsernameAction
	| IPageSetPluginDataAction
	| IPageUpdateSavedDataAction
	| IPageJoinGroupAction
	| IPageChangePrimaryGroupAction
;

function reducer(state: IPageState, action: PageAction): IPageState
{
	let { savedData } = state;
	const { data } = state;
	const { user } = data;
	let { username, groups, primary_group, id, is_superuser  } = user;
	const pluginData = {...data.pluginData};

	const groupSet = new Set(groups);
	
	if (action.type === 'setUsername')
	{
		username = action.username;
	}
	else if (action.type === 'setPluginData')
	{
		const { key, data } = action;
		pluginData[key] = data;
	}
	else if (action.type === 'updateSavedData')
	{
		savedData = action.savedData;
	}
	else if (action.type === 'joinGroup')
	{
		if (action.groupId === primary_group)
			throw new Error('Cannot edit primary group membership');

		if (action.inGroup)
		{
			groupSet.add(action.groupId);
		}
		else
		{
			groupSet.delete(action.groupId);
		}
	}
	else if (action.type === 'changePrimaryGroup')
	{
		if (!groupSet.has(action.groupId))
			throw new Error('Must set primary group to already joined group');

		primary_group = action.groupId;
	}
	else
	{
		throw new Error('Unknown action type');
	}

	groups = Array.from(groupSet);

	return { savedData, data: {
		user: {
			username,
			id,
			groups,
			primary_group,
			is_superuser
		},
		pluginData
	} };
}

interface IPageModel
{
	errorMsg?: string|null;
	user: IUser;
	patterns: IPatterns;
	authPlugins: IAuthPluginData[];
	groups: Groups;
}

interface IPageProps extends IPageModel
{
	pluginModules: { [key: string]: IAuthPluginScriptModule };
}

function Page(props: IPageProps)
{
	const { user, patterns, authPlugins, pluginModules, groups } = props;

	const [errorMsg, setErrorMsg] = useState(props.errorMsg);

	const userId = user.id;

	const initState = useMemo(() => {
		const data: IFormData = {
			user,
			pluginData: {}
		};

		const pluginHasChange: { [key: string]: boolean } = {};

		for (const p of authPlugins)
		{
			data.pluginData[p.key] = p.data;
			pluginHasChange[p.key] = false;
		}

		return { data, savedData: data, pluginHasChange };
	}, [user, userId, authPlugins]);

	const [state, dispatch] = useReducer(reducer, initState); 

	const { data, savedData } = state;

	const plugins: ReactNode[] = [];
	let pluginHasChange = false;

	for (const p of authPlugins)
	{
		const { modelEquals, UserEditor } = pluginModules[p.key];
		pluginHasChange = pluginHasChange
			|| !modelEquals(data.pluginData[p.key], savedData.pluginData[p.key]);

		plugins.push(<section
				className="section"
				key={p.key}
				>
				<h3> {p.title} </h3>
				<UserEditor
					data={data.pluginData[p.key]}
					setData={(data: any) => dispatch(
						{ type: 'setPluginData', key: p.key, data }
					)}
				/>
		</section>);
	}

	const onSave = useCallback(async () => {
		const submittedData = structuredClone(data);
		
		const { errorMsg } = await postJson<ISaveReponse>('/site/admin/users', {
			body: submittedData,
			query: {
				action: 'save'
			}
		});

		setErrorMsg(errorMsg);

		if (errorMsg)
			return;

		dispatch({ type: 'updateSavedData', savedData: submittedData });
		
	}, [data]);

	const hasChange = !userEquals(data.user, savedData.user)
		|| pluginHasChange;

	const setUsername = useCallback((e: ChangeEvent<HTMLInputElement>) => {
		dispatch({ type: 'setUsername', username: e.target.value });
	}, []);

	const setPrimaryGroup = useCallback((e: ChangeEvent<HTMLSelectElement>) => {
		const val = parseInt(e.target.value);
		dispatch({ type: 'changePrimaryGroup', groupId: val });
	}, []);

	const primaryGroupOptions = data.user.groups.map((gid) => {
		return <option value={gid} key={gid}>
			{groups[`${gid}`].groupname}
		</option>;
	});

	return <div className="editor">
		<UserDispatchContext.Provider value={dispatch}>
		<ModalErrorMsg msg={errorMsg || null} />

		<h1> Edit User </h1>
		<AutoSaveForm onSave={onSave} hasChange={hasChange} />
			<div className="section-container">

				<section className="section">
					<h3> User Properties </h3>
					<div>
						<label> username:
							<input type="text"
								className="editable"
								name="username"
								title="Enter a username (letters, numbers, or underscores)"
								pattern={patterns.username}
								value={data.user.username}
								onChange={setUsername}
								required
								/>
						</label>
					</div>
					<div>
						<label> Primary Group:
							<select
								onChange={setPrimaryGroup}
								value={data.user.primary_group}
							>
								{primaryGroupOptions}
							</select>
						</label>
					</div>
				
				</section>

				<GroupMembership
					allGroups={groups}
					groupMembership={data.user.groups}
					primaryId={data.user.primary_group}
				/>

				{plugins}
			</div>

			<p className="status-bar">
				<SaveIndicator isSaving={hasChange} hasError={!!errorMsg} />
			</p>

		</UserDispatchContext.Provider>
	</div>;
}

renderReactPage<IPageModel>(async (model) => {
	const promises: Promise<void>[] = [];
	const modules: { [key: string]: IAuthPluginScriptModule } = {};

	for (const p of model.authPlugins)
	{
		promises.push(new Promise(async (res) => {
			modules[p.key] = await requireAsync<IAuthPluginScriptModule>(p.script);
			res();
		}));
	}

	await Promise.all(promises);

	return <Page {...model} pluginModules={modules} />;
});
