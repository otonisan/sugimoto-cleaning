import { execSync } from 'node:child_process';
import path from 'node:path';

// リポジトリのルート（tests/e2e/helpers から3つ上）。
const ROOT = path.resolve(__dirname, '../../..');

/**
 * wp-env の cli コンテナで WP-CLI を実行し、標準出力を返す。
 * Windows（cmd.exe）と sh の両方で同じように動くよう、二重引用符を含む引数は受け付けない。
 */
export function wpCli(args: string[]): string {
  const quoted = args.map((arg) => {
    if (arg.includes('"')) {
      throw new Error(`wpCli: 二重引用符を含む引数は使えません: ${arg}`);
    }
    return /[\s'&|<>^]/.test(arg) ? `"${arg}"` : arg;
  });
  return execSync(`npx wp-env run cli wp ${quoted.join(' ')}`, {
    cwd: ROOT,
    encoding: 'utf8',
    stdio: ['ignore', 'pipe', 'pipe'],
  }).trim();
}

/**
 * ブランド設定（spc_settings）の1項目を書き換え、元に戻す関数を返す。
 * オプションや項目が元々なかった場合は、削除して元の状態に戻す。
 */
export function setSpcSetting(key: string, value: string): () => void {
  let before: Record<string, unknown> | null = null;
  try {
    before = JSON.parse(wpCli(['option', 'get', 'spc_settings', '--format=json']));
  } catch {
    before = null;
  }
  if (before === null) {
    wpCli(['option', 'add', 'spc_settings', '{}', '--format=json']);
  }
  const hadKey = before !== null && Object.prototype.hasOwnProperty.call(before, key);
  wpCli(['option', 'patch', hadKey ? 'update' : 'insert', 'spc_settings', key, value]);

  return () => {
    if (before === null) {
      wpCli(['option', 'delete', 'spc_settings']);
    } else if (!hadKey) {
      wpCli(['option', 'patch', 'delete', 'spc_settings', key]);
    } else {
      wpCli(['option', 'patch', 'update', 'spc_settings', key, String(before[key])]);
    }
  };
}
