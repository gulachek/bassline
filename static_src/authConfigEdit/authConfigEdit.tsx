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
  ReactNode,
} from 'react';

import { renderReactPage } from '../renderReactPage';
import { postJson } from '../postJson';
import { requireAsync } from '../requireAsync';
import { AuthPluginConfigEditComponent } from './authPluginConfigEdit';
import { AutoSaveForm } from '../autosave/AutoSaveForm';
import { SaveIndicator } from '../autosave/SaveIndicator';
import { ErrorBanner } from '../ErrorBanner';

import './authConfigEdit.scss';

interface ISaveResponse {
  errorMsg?: string | null;
  newSaveKey?: string | null;
}

const AuthConfigDispatchContext = createContext(null);

type PluginDataEqualFn = (data: any, savedData: any) => boolean;

interface IAuthPluginScriptModule {
  ConfigEditor: AuthPluginConfigEditComponent;
  modelEquals: PluginDataEqualFn;
}

interface IAuthPluginData {
  key: string;
  script: string;
  title: string;
  data: any;
}

interface IFormData {
  // weird name
  pluginData: { [key: string]: any };
}

interface ISaveRequest {
  pluginData: { [key: string]: any };
  saveKey: string;
}

interface IPageState {
  data: IFormData;
  savedData: IFormData;
  saveKey: string;
  errorMsg: string;
  isSaving: boolean;
}

interface IPageSetPluginDataAction {
  type: 'setPluginData';
  key: string;
  data: any;
}

interface IPageBeginSave {
  type: 'beginSave';
}

interface IPageEndSave {
  type: 'endSave';
  savedData: IFormData;
  response: ISaveResponse;
}

type PageAction = IPageSetPluginDataAction | IPageBeginSave | IPageEndSave;

function reducer(state: IPageState, action: PageAction): IPageState {
  let { savedData, saveKey, errorMsg, isSaving } = state;
  const { data } = state;
  const pluginData = { ...data.pluginData };

  if (action.type === 'setPluginData') {
    const { key, data } = action;
    pluginData[key] = data;
  } else if (action.type === 'beginSave') {
    isSaving = true;
  } else if (action.type === 'endSave') {
    isSaving = false;
    if (action.response.errorMsg) {
      console.error(action.response.errorMsg);
      errorMsg = action.response.errorMsg;
    } else {
      savedData = action.savedData;
      saveKey = action.response.newSaveKey;
    }
  } else {
    throw new Error('Unknown action type');
  }

  return { savedData, saveKey, errorMsg, isSaving, data: { pluginData } };
}

interface IPageModel {
  errorMsg?: string | null;
  authPlugins: IAuthPluginData[];
  initialSaveKey: string;
}

interface IPageProps extends IPageModel {
  pluginModules: { [key: string]: IAuthPluginScriptModule };
}

function Page(props: IPageProps) {
  const { authPlugins, pluginModules } = props;

  const initState = useMemo(() => {
    const data: IFormData = {
      pluginData: {},
    };

    const pluginHasChange: { [key: string]: boolean } = {};

    for (const p of authPlugins) {
      data.pluginData[p.key] = p.data;
      pluginHasChange[p.key] = false;
    }

    return {
      data,
      savedData: data,
      pluginHasChange,
      saveKey: props.initialSaveKey,
      errorMsg: null,
      isSaving: false,
    };
  }, [authPlugins, props.initialSaveKey]);

  const [state, dispatch] = useReducer(reducer, initState);

  const { data, savedData, errorMsg, saveKey, isSaving } = state;

  const plugins: ReactNode[] = [];
  let pluginHasChange = false;

  for (const p of authPlugins) {
    const { modelEquals, ConfigEditor } = pluginModules[p.key];
    pluginHasChange =
      pluginHasChange ||
      !modelEquals(data.pluginData[p.key], savedData.pluginData[p.key]);

    plugins.push(
      <section className="section" key={p.key} data-plugin-key={p.key}>
        <h3> {p.title} </h3>
        <ConfigEditor
          data={data.pluginData[p.key]}
          setData={(data: any) =>
            dispatch({ type: 'setPluginData', key: p.key, data })
          }
        />
      </section>
    );
  }

  const onSave = useCallback(async () => {
    dispatch({ type: 'beginSave' });

    const submittedData = structuredClone(data);
    const request: ISaveRequest = {
      pluginData: submittedData.pluginData,
      saveKey,
    };

    const response = await postJson<ISaveResponse>(
      '/site/admin/auth_config/save',
      {
        body: request,
      }
    );

    dispatch({ type: 'endSave', savedData: submittedData, response });
  }, [data, saveKey]);

  const shouldSave = pluginHasChange && !errorMsg;

  return (
    <div className="editor">
      <AuthConfigDispatchContext.Provider value={dispatch}>
        {errorMsg && <ErrorBanner msg={errorMsg} />}

        <h1> Authentication Configuration </h1>
        <AutoSaveForm onSave={onSave} shouldSave={shouldSave && !isSaving} />

        <div className="section-container">{plugins}</div>

        <p className="status-bar">
          <SaveIndicator
            isSaving={shouldSave || isSaving}
            hasError={!!errorMsg}
          />
        </p>
      </AuthConfigDispatchContext.Provider>
    </div>
  );
}

renderReactPage<IPageModel>(async (model) => {
  const promises: Promise<void>[] = [];
  const modules: { [key: string]: IAuthPluginScriptModule } = {};

  for (const p of model.authPlugins) {
    promises.push(
      new Promise(async (res) => {
        modules[p.key] = await requireAsync<IAuthPluginScriptModule>(p.script);
        res();
      })
    );
  }

  await Promise.all(promises);

  return <Page {...model} pluginModules={modules} />;
});
