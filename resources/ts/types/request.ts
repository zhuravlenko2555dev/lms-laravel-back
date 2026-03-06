type QueryPrimitive = string | number
type QueryParam = QueryPrimitive | QueryPrimitive[]
export type QueryParams = Record<string, QueryParam>
