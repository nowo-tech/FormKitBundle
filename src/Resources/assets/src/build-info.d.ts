declare module '*?raw' {
  const content: string;
  export default content;
}
declare module '*build-info.json' {
  const info: { sourceHash: string; builtAt: string };
  export default info;
}
