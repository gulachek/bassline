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
	ReactNode
} from 'react';

import { renderReactPage } from '../renderReactPage';
import { postJson } from '../postJson';
import { requireAsync } from '../requireAsync';
import { AuthPluginConfigEditComponent } from './authPluginConfigEdit';
import { AutoSaveForm } from '../autosave/AutoSaveForm';
import { SaveIndicator } from '../autosave/SaveIndicator';

import './authConfigEdit.scss';

interface ISaveReponse
{
	errorMsg?: string|null;
}

const AuthConfigDispatchContext = createContext(null);

type PluginDataEqualFn = (data: any, savedData: any) => boolean;

interface IAuthPluginScriptModule
{
	ConfigEditor: AuthPluginConfigEditComponent;
	modelEquals: PluginDataEqualFn;
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

interface IFormData
{
	pluginData: { [key: string]: any };
}

interface IPageState
{
	data: IFormData;
	savedData: IFormData;
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
	| IPageSetPluginDataAction
	| IPageUpdateSavedDataAction
;

function reducer(state: IPageState, action: PageAction): IPageState
{
	let { savedData } = state;
	const { data } = state;
	const pluginData = {...data.pluginData};

	if (action.type === 'setPluginData')
	{
		const { key, data } = action;
		pluginData[key] = data;
	}
	else if (action.type === 'updateSavedData')
	{
		savedData = action.savedData;
	}
	else
	{
		throw new Error('Unknown action type');
	}

	return { savedData, data: { pluginData } };
}

interface IPageModel
{
	errorMsg?: string|null;
	authPlugins: IAuthPluginData[];
}

interface IPageProps extends IPageModel
{
	pluginModules: { [key: string]: IAuthPluginScriptModule };
}

function Page(props: IPageProps)
{
	const { authPlugins, pluginModules } = props;

	const [errorMsg, setErrorMsg] = useState(props.errorMsg);

	const initState = useMemo(() => {
		const data: IFormData = {
			pluginData: {}
		};

		const pluginHasChange: { [key: string]: boolean } = {};

		for (const p of authPlugins)
		{
			data.pluginData[p.key] = p.data;
			pluginHasChange[p.key] = false;
		}

		return { data, savedData: data, pluginHasChange };
	}, [authPlugins]);

	const [state, dispatch] = useReducer(reducer, initState); 

	const { data, savedData } = state;

	const plugins: ReactNode[] = [];
	let pluginHasChange = false;

	for (const p of authPlugins)
	{
		const { modelEquals, ConfigEditor } = pluginModules[p.key];
		pluginHasChange = pluginHasChange
			|| !modelEquals(data.pluginData[p.key], savedData.pluginData[p.key]);

		plugins.push(<section
				className="section"
				key={p.key}
				data-plugin-key={p.key}
				>
				<h3> {p.title} </h3>
				<ConfigEditor
					data={data.pluginData[p.key]}
					setData={(data: any) => dispatch(
						{ type: 'setPluginData', key: p.key, data }
					)}
				/>
		</section>);
	}

	const onSave = useCallback(async () => {
		const submittedData = structuredClone(data);
		
		const { errorMsg } = await postJson<ISaveReponse>('/site/admin/auth_config/save', {
			body: submittedData,
		});

		setErrorMsg(errorMsg);

		if (errorMsg)
			return;

		dispatch({ type: 'updateSavedData', savedData: submittedData });
		
	}, [data]);

	return <div className="editor">
		<AuthConfigDispatchContext.Provider value={dispatch}>
		<ModalErrorMsg msg={errorMsg || null} />

		<h1> Authentication Configuration </h1>
		<AutoSaveForm onSave={onSave} hasChange={pluginHasChange} />

		<div className="section-container">
			{plugins}
		</div>


		<p className="status-bar">
			<SaveIndicator isSaving={pluginHasChange} hasError={false} />
		</p>
		</AuthConfigDispatchContext.Provider>
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
