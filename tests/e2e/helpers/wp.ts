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
 * 投稿（固定ページなど）の本文を、任意のHTML（二重引用符を含んでもよい）に書き換える。
 * HTML は Node で base64 にして PHP の単引用符の文字列で渡す（wpCli は二重引用符を受け付けないため）。ファイルは作らない。
 */
export function setPostContent(id: number | string, html: string): void {
  const postId = Number(id);
  if (!Number.isInteger(postId) || postId <= 0) {
    throw new Error(`setPostContent: 投稿IDが不正です: ${id}`);
  }
  const b64 = Buffer.from(html, 'utf8').toString('base64');
  wpCli(['eval', `wp_update_post( array( 'ID' => ${postId}, 'post_content' => base64_decode( '${b64}' ) ) );`]);
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

/** 公開中の施工事例のID。 */
export function publishedCaseIds(): string[] {
  const out = wpCli(['post', 'list', '--post_type=spc_case', '--post_status=publish', '--format=ids']);
  return out.split(/\s+/).filter(Boolean);
}

/** 投稿をゴミ箱に入れる。WP-CLI の post delete は投稿・固定ページ以外をゴミ箱に入れられないため、wp_trash_post を使う。 */
export function trash(ids: string[]): void {
  wpCli(['eval', `array_map('wp_trash_post',array(${ids.join(',')}));`]);
}

/** ゴミ箱の投稿を元の公開状態に戻す（WP 5.6 以降は既定で下書きに戻るため、フィルタで元の状態を使う）。 */
export function untrash(ids: string[]): void {
  wpCli([
    'eval',
    `add_filter('wp_untrash_post_status','wp_untrash_post_set_previous_status',10,3);array_map('wp_untrash_post',array(${ids.join(',')}));`,
  ]);
}
