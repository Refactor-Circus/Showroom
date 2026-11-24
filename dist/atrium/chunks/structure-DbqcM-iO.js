import { C as e, D as t, T as n, a as r } from "./catalog-DCoO3A8a.js";
// src/components/structure/structure.ts
var i = [
	"text",
	"textarea",
	"number",
	"decimal",
	"boolean",
	"date",
	"select",
	"multiselect",
	"price",
	"metric"
];
function a(e) {
	return e === "select" || e === "multiselect";
}
var o = [
	"is_unique",
	"is_localizable",
	"is_scopable"
], s = ["product", "owner"];
function c(e) {
	return {
		locales: [...e?.locales ?? []],
		currencies: (e?.currencies ?? []).join(", "),
		category_tree: e?.category_tree ?? ""
	};
}
function l(e) {
	return {
		locales: [...e.locales],
		currencies: n(e.currencies),
		category_tree: e.category_tree || null
	};
}
function u(e, t) {
	let n = r(t);
	return n ? { labels: { [e]: n } } : {};
}
function d(e, t, n) {
	return {
		...e,
		[t]: r(n)
	};
}
function f(e) {
	let t = String(e ?? "").trim(), n = Number(t);
	return t === "" || !Number.isFinite(n) ? void 0 : n;
}
function p(e) {
	let t = f(e);
	return t === void 0 ? {} : { sort_order: t };
}
function m(e) {
	if (e.trim() === "") return {};
	try {
		let t = JSON.parse(e);
		return typeof t == "object" && t && !Array.isArray(t) ? t : null;
	} catch {
		return null;
	}
}
function h(e) {
	return e && Object.keys(e).length > 0 ? JSON.stringify(e, null, 2) : "";
}
async function g(e, t = 20) {
	let n = [], r;
	for (let i = 0; i < t; i++) {
		let t = await e(r);
		if (n.push(...t.data), r = t.meta?.next_cursor ?? void 0, !r) break;
	}
	return n;
}
async function _(t, n) {
	return g(async (r) => e(await t.GET("/categories", { params: { query: {
		under: n,
		per_page: 100,
		...r ? { cursor: r } : {}
	} } })));
}
async function v(t, n) {
	let r = [n], i = n.parent ?? null;
	for (let a = 0; i !== null && a < Math.max(n.depth, 0); a++) {
		let n = i, a = e(await t.GET("/categories/{category}", { params: { path: { category: n } } })).data;
		r.unshift(a), i = a.parent ?? null;
	}
	return r;
}
function y(e, t) {
	return t.filter((t) => t.parent === e).map((e) => ({
		category: e,
		children: y(e.code, t)
	}));
}
async function b(n) {
	return e(await n.GET("/categories", { params: { query: t({
		roots: 1,
		per_page: 100
	}) } }));
}
var x = (e) => encodeURIComponent(e);
function S(e) {
	return `/showroom/attribute-groups/${x(e)}`;
}
function C(e) {
	return `/showroom/channels/${x(e)}`;
}
function w(e) {
	return `/showroom/flags/${x(e)}`;
}
function T(e) {
	return `/showroom/products?category=${x(e)}`;
}
export { m as _, y as a, p as b, c, w as d, a as f, u as g, b as h, S as i, C as l, v as m, i as n, T as o, _ as p, s as r, l as s, o as t, d as u, h as v, f as y };
