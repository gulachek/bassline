import * as React from 'react';
import { useCallback, ChangeEvent } from 'react';

import { AuthPluginConfigEditComponent } from '../authConfigEdit/authPluginConfigEdit';

interface IData {
  visible: boolean;
}

export const ConfigEditor: AuthPluginConfigEditComponent<IData> = (props) => {
  const { data, setData } = props;
  const onChange = useCallback((e: ChangeEvent<HTMLInputElement>) => {
    setData({ visible: e.target.checked });
  }, []);

  return (
    <label className="nonce-visible">
      <input type="checkbox" checked={data.visible} onChange={onChange} />
      visible
    </label>
  );
};

export function modelEquals(a: IData, b: IData) {
  return a.visible === b.visible;
}
