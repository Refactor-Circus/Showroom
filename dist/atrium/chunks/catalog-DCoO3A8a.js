import { AtriumIntegrationError as e, atriumStatus as t, useAtriumIntegration as n } from "@refactor-circus/atrium";
import { ref as r, shallowRef as i, watch as a } from "vue";
// src/components/catalog/catalog.ts
var o = [
	"draft",
	"in_review",
	"approved",
	"archived"
], s = [
	{
		name: "submit",
		from: ["draft"],
		icon: "i-lucide-send"
	},
	{
		name: "approve",
		from: ["in_review"],
		icon: "i-lucide-circle-check"
	},
	{
		name: "reject",
		from: ["in_review"],
		icon: "i-lucide-circle-x"
	},
	{
		name: "publish",
		from: ["approved"],
		icon: "i-lucide-rocket"
	},
	{
		name: "unpublish",
		from: [
			"draft",
			"in_review",
			"approved"
		],
		icon: "i-lucide-eye-off"
	},
	{
		name: "archive",
		from: [
			"draft",
			"in_review",
			"approved"
		],
		icon: "i-lucide-archive"
	},
	{
		name: "restore",
		from: ["archived"],
		icon: "i-lucide-archive-restore"
	}
];
function c(e, t) {
	return s.filter((n) => n.from.includes(e) && (n.name !== "unpublish" || t !== null)).map((e) => e.name);
}
function l(e) {
	switch (e) {
		case "in_review": return "info";
		case "approved": return "success";
		case "draft": return "primary";
		default: return "neutral";
	}
}
function u() {
	return n("showroom").client;
}
function d(t) {
	if (!t.response.ok) throw new e(t.response.status, t.error);
	return t.data;
}
function f(e) {
	return e;
}
function p(e, n) {
	let o = u(), s = i(null), c = r(!0), l = r(!1), d = r(null);
	async function f() {
		if (!o) c.value = !1;
		else {
			c.value = s.value === null;
			try {
				s.value = await e(o), l.value = !1, d.value = null;
			} catch (e) {
				l.value = !0, d.value = t(e);
			} finally {
				c.value = !1;
			}
		}
	}
	return n && a(n, () => void f(), { deep: !0 }), f(), {
		client: o,
		data: s,
		pending: c,
		failed: l,
		status: d,
		reload: f
	};
}
function m(e) {
	let t = u(), n = i([]);
	return t && e(t).then((e) => {
		n.value = e.data;
	}).catch(() => {
		n.value = [];
	}), n;
}
function h() {
	let n = r(!1), i = r({}), a = r(!1), o = r(null);
	async function s(r) {
		n.value = !0, i.value = {}, a.value = !1, o.value = null;
		try {
			return await r(), !0;
		} catch (n) {
			let r = t(n), s = n instanceof e ? n.body : null;
			return r === 403 ? a.value = !0 : r === 422 && s?.errors ? i.value = s.errors : o.value = typeof s?.message == "string" ? s.message : n instanceof Error ? n.message : String(n), !1;
		} finally {
			n.value = !1;
		}
	}
	function c(e, t = !1) {
		let n = i.value[e]?.[0];
		if (n || t) return n;
		let r = Object.keys(i.value).find((t) => t.startsWith(`${e}.`));
		return r ? i.value[r]?.[0] : void 0;
	}
	return {
		busy: n,
		errors: i,
		forbidden: a,
		message: o,
		run: s,
		error: c
	};
}
function g(e) {
	let t = Array.isArray(e) ? e[0] : e;
	return typeof t == "string" && t !== "" ? t : void 0;
}
function _(e) {
	let t = Number(g(e));
	return Number.isInteger(t) && t > 1 ? t : 1;
}
function v(e, t, n) {
	return e?.[t] || (Object.values(e ?? {}).find((e) => typeof e == "string" && e !== "") ?? n);
}
function y(e) {
	if (!e) return null;
	let t = new Date(e);
	return Number.isNaN(t.getTime()) ? e : t.toLocaleString();
}
var b = (e) => encodeURIComponent(e);
function x(e) {
	return `/showroom/products/${b(e)}`;
}
function S(e) {
	return `/showroom/products?search=${b(e)}`;
}
function C(e) {
	return `/showroom/product-models/${b(e)}`;
}
function w(e) {
	return `/showroom/families/${b(e)}`;
}
function T(e) {
	return `/showroom/family-variants/${b(e)}`;
}
function E(e) {
	return `/showroom/attributes/${b(e)}`;
}
function D(e) {
	return `/showroom/categories/${b(e)}`;
}
function O(e) {
	return [...new Set(e.split(",").map((e) => e.trim()).filter((e) => e !== ""))];
}
function k(e) {
	let t = (e ?? "").trim();
	return t === "" ? null : t;
}
function A(e) {
	return {
		ids: { ...e },
		source: "",
		id: ""
	};
}
function j(e) {
	let t = {};
	for (let [n, r] of Object.entries(e.ids)) t[n] = k(r);
	let n = e.source.trim(), r = e.id.trim();
	return n !== "" && r !== "" && (t[n] = r), t;
}
function M(e, t, n) {
	let r = t ? e?.[t] ?? [] : [], i = (r.find((e) => e.locale === n) ?? r.find((e) => e.locale === null) ?? r[0])?.data;
	return typeof i == "string" || typeof i == "number" ? String(i) : null;
}
function N(e, t, n, r) {
	return e?.[t]?.find((e) => e.scope === n && e.locale === r)?.data;
}
function P(e, t) {
	return JSON.stringify(e) === JSON.stringify(t);
}
function F(e, t) {
	let n = {};
	for (let [r, i] of Object.entries(e ?? {})) {
		let e = i.filter((e) => !(t?.[r] ?? []).some((t) => t.scope === e.scope && t.locale === e.locale && P(t.data, e.data)));
		e.length > 0 && (n[r] = e);
	}
	return n;
}
function I(e, t) {
	return e.filter((e) => !t.includes(e));
}
function L(e, t) {
	let n = {};
	for (let [r, i] of Object.entries(e ?? {})) n[r] = {
		products: i.products.filter((e) => !(t?.[r]?.products ?? []).includes(e)),
		product_models: i.product_models.filter((e) => !(t?.[r]?.product_models ?? []).includes(e))
	};
	return n;
}
function R(e, t) {
	let n = {}, r = (e, t) => e.filter((e) => !(t ?? []).some((t) => t.identifier === e.identifier && t.quantity === e.quantity));
	for (let [i, a] of Object.entries(e ?? {})) n[i] = {
		products: r(a.products, t?.[i]?.products),
		product_models: r(a.product_models, t?.[i]?.product_models)
	};
	return n;
}
export { m as A, d as C, f as D, l as E, h as O, g as S, O as T, F as _, k as a, S as b, A as c, y as d, v as f, R as g, I as h, c as i, u as j, p as k, w as l, L as m, s as n, D as o, M as p, E as r, j as s, o as t, T as u, C as v, N as w, _ as x, x as y };
