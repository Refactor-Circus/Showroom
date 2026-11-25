import { C as e, j as t, w as n } from "./catalog-DCoO3A8a.js";
import { computed as r, shallowRef as i, watch as a } from "vue";
import { useI18n as o } from "vue-i18n";
import { useRoute as s, useRouter as c } from "vue-router";
// src/components/catalog/values.ts
function l(e, t) {
	return !(e.is_localizable && t.locale === null || e.is_scopable && t.channel === null);
}
function u(e, t) {
	return {
		locale: e.is_localizable ? t.locale : null,
		scope: e.is_scopable ? t.channel : null
	};
}
function d(e, t) {
	let n = e.settings.currencies;
	if (Array.isArray(n) && n.length > 0) return n.filter((e) => typeof e == "string");
	let r = t && typeof t == "object" && !Array.isArray(t) ? Object.keys(t).filter((e) => e !== "amount" && e !== "unit") : [];
	return r.length > 0 ? r : ["USD"];
}
function f(e) {
	return e == null ? "" : typeof e == "object" ? JSON.stringify(e) : String(e);
}
function p(e, t) {
	switch (e.type) {
		case "boolean": return t === !0 ? "1" : t === !1 ? "0" : "";
		case "multiselect": return Array.isArray(t) ? t.filter((e) => typeof e == "string") : [];
		case "metric": {
			let n = t ?? {}, r = typeof e.settings.default_unit == "string" ? e.settings.default_unit : "";
			return {
				amount: f(n.amount),
				unit: n.unit === void 0 || n.unit === null ? r : f(n.unit)
			};
		}
		case "price": {
			let e = {};
			for (let n of Array.isArray(t) ? t : []) typeof n.currency == "string" && (e[n.currency] = f(n.amount));
			return e;
		}
		default: return f(t);
	}
}
function m(e, t) {
	if (t === void 0 || t === "" || Array.isArray(t) && t.length === 0) return null;
	switch (e.type) {
		case "boolean": return t === "1";
		case "number": return typeof t == "string" && Number.isFinite(Number(t)) ? Number(t) : t;
		case "multiselect": return Array.isArray(t) ? t.filter((e) => e !== "") : null;
		case "metric": {
			let e = t;
			return e.amount === "" ? null : {
				amount: e.amount,
				unit: e.unit
			};
		}
		case "price": {
			let e = Object.entries(t).filter(([, e]) => e !== "").map(([e, t]) => ({
				amount: t,
				currency: e
			}));
			return e.length > 0 ? e : null;
		}
		default: return t;
	}
}
function h(e, t, r) {
	return Object.fromEntries(e.map((e) => {
		let { locale: i, scope: a } = u(e, r);
		return [e.code, p(e, n(t, e.code, a, i))];
	}));
}
function g(e, t, n) {
	let r = {};
	for (let i of e) l(i, n) && (r[i.code] = [{
		...u(i, n),
		data: m(i, t[i.code])
	}]);
	return r;
}
function _(e, t) {
	return Object.fromEntries(e.map((e) => [e.code, [{
		locale: null,
		scope: null,
		data: m(e, t[e.code])
	}]]));
}
function v(e) {
	return Object.fromEntries(e.map((e) => [e.code, p(e, null)]));
}
function y(e, t) {
	return e ? t === 0 ? e.common_attributes ?? [] : e.levels?.find((e) => e.level === t)?.attributes ?? [] : [];
}
function b(e, t) {
	return e?.levels?.find((e) => e.level === t)?.axes ?? [];
}
async function x(t) {
	let n = [], r;
	for (let i = 0; i < 10; i++) {
		let i = e(await t.GET("/attributes", { params: { query: {
			per_page: 100,
			...r ? { cursor: r } : {}
		} } }));
		if (n.push(...i.data), r = i.meta?.next_cursor ?? void 0, !r) break;
	}
	return n;
}
function S(n) {
	let r = t(), o = i(/* @__PURE__ */ new Map()), s = i(/* @__PURE__ */ new Map()), c = null;
	async function l() {
		if (r) try {
			o.value = new Map((await x(r)).map((e) => [e.code, e]));
		} catch {
			o.value = /* @__PURE__ */ new Map();
		}
	}
	async function u(t) {
		c ??= l(), await c;
		let n = t.filter((e) => {
			let t = o.value.get(e)?.type;
			return (t === "select" || t === "multiselect") && !s.value.has(e);
		});
		if (!r || n.length === 0) return;
		let i = new Map(s.value);
		await Promise.all(n.map(async (t) => {
			try {
				i.set(t, e(await r.GET("/attributes/{attribute}/options", { params: {
					path: { attribute: t },
					query: { per_page: 100 }
				} })).data);
			} catch {
				i.set(t, []);
			}
		})), s.value = i;
	}
	a(() => [...n()], (e) => void u(e), { immediate: !0 });
	function d(e) {
		return e.flatMap((e) => {
			let t = o.value.get(e);
			return t ? [{
				...t,
				options: s.value.get(e) ?? []
			}] : [];
		});
	}
	return {
		attributes: o,
		pick: d
	};
}
function C() {
	let n = t(), a = s(), l = c(), { locale: u } = o(), d = i([]), f = i([]);
	n && (n.GET("/locales", { params: { query: { per_page: 100 } } }).then((t) => {
		d.value = e(t).data;
	}).catch(() => void 0), n.GET("/channels", { params: { query: { per_page: 100 } } }).then((t) => {
		f.value = e(t).data;
	}).catch(() => void 0));
	let p = r(() => {
		let e = d.value.map((e) => e.code), t = f.value.map((e) => e.code), n = typeof a.query.locale == "string" ? a.query.locale : null, r = typeof a.query.channel == "string" ? a.query.channel : null;
		return {
			locale: n && e.includes(n) ? n : e.includes(u.value) ? u.value : e[0] ?? null,
			channel: r && t.includes(r) ? r : t[0] ?? null
		};
	});
	function m(e) {
		l.replace({ query: {
			...a.query,
			locale: e.locale ?? void 0,
			channel: e.channel ?? void 0
		} });
	}
	return {
		locales: d,
		channels: f,
		slot: p,
		pick: m
	};
}
export { b as a, C as c, y as i, h as l, v as n, l as o, d as r, S as s, _ as t, g as u };
