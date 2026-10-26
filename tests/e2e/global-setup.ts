import { wpCli } from './helpers/wp';

// e2e の前に仮データを投入する（何度実行しても重複しない）。
export default async function globalSetup(): Promise<void> {
  wpCli(['spc', 'seed']);
}
