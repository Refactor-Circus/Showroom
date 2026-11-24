import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { C as t, O as n, b as r, f as i, j as a, v as o } from "./catalog-DCoO3A8a.js";
import { o as s, r as c } from "./values-B4RtDnRM.js";
import { UButton as l, UCard as u, UFormField as d, UInput as f } from "@refactor-circus/atrium/ui";
import { Fragment as p, computed as m, createBlock as h, createCommentVNode as g, createElementBlock as _, createElementVNode as v, createTextVNode as y, createVNode as b, defineComponent as x, isRef as S, mergeModels as C, normalizeClass as w, openBlock as T, reactive as E, ref as D, renderList as O, resolveComponent as k, shallowRef as A, toDisplayString as j, unref as M, useModel as N, useTemplateRef as P, vModelSelect as F, watch as I, withCtx as L, withDirectives as R, withModifiers as z } from "vue";
import { useI18n as B } from "vue-i18n";
// src/components/catalog/CatalogAssets.vue?vue&type=script&setup=true&lang.ts
var V = { class: "font-semibold" }, ee = { class: "space-y-4" }, te = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-assets-empty"
}, ne = {
	key: 1,
	class: "grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6"
}, H = ["src", "alt"], U = {
	key: 1,
	class: "flex aspect-square items-center justify-center rounded bg-elevated font-mono text-xs text-muted"
}, W = { class: "truncate text-xs" }, G = /* @__PURE__ */ x({
	__name: "CatalogAssets",
	props: {
		assets: {},
		linkType: {},
		linkTarget: {}
	},
	emits: ["changed"],
	setup(r, { emit: i }) {
		let o = r, s = i, { t: c } = B(), m = a(), g = n(), x = A(null), C = D("image"), w = P("fileInput");
		function E(e) {
			x.value = e.target.files?.[0] ?? null;
		}
		function N(e) {
			return !!e.url && (e.mime_type ?? "").startsWith("image/");
		}
		function F(e) {
			return [e.role, e.inherited ? c("showroom.catalog.inherited") : e.code].filter((e) => e).join(" · ");
		}
		async function I() {
			let e = x.value;
			m && e && await g.run(async () => {
				let n = new FormData();
				n.append("file", e);
				let r = t(await m.POST("/assets", {
					body: n,
					bodySerializer: (e) => e
				})).data;
				t(await m.POST("/assets/{asset}/links", {
					params: { path: { asset: r.code } },
					body: {
						type: o.linkType,
						target: o.linkTarget,
						...C.value.trim() ? { role: C.value.trim() } : {}
					}
				}));
			}) && (x.value = null, w.value && (w.value.value = ""), s("changed"));
		}
		return (t, n) => {
			let r = k("RouterLink"), i = d, a = f, s = l, m = u;
			return T(), h(m, { "data-testid": "showroom-assets" }, {
				header: L(() => [v("h2", V, j(M(c)("showroom.catalog.assets.title")), 1)]),
				default: L(() => [v("div", ee, [
					o.assets.length === 0 ? (T(), _("p", te, j(M(c)("showroom.catalog.assets.none")), 1)) : (T(), _("div", ne, [(T(!0), _(p, null, O(o.assets, (e) => (T(), h(r, {
						key: `${e.inherited ? "inherited" : "own"}-${e.code}`,
						to: `/showroom/assets/${encodeURIComponent(e.code)}`,
						class: "flex flex-col gap-2 rounded-md border border-default p-2 hover:border-primary",
						"data-testid": `showroom-asset-${e.code}`
					}, {
						default: L(() => [N(e) ? (T(), _("img", {
							key: 0,
							src: e.url ?? void 0,
							alt: e.code,
							class: "aspect-square w-full rounded object-cover"
						}, null, 8, H)) : (T(), _("span", U, j(e.mime_type ?? e.code), 1)), v("span", W, j(F(e)), 1)]),
						_: 2
					}, 1032, ["to", "data-testid"]))), 128))])),
					b(e, { action: M(g) }, null, 8, ["action"]),
					v("form", {
						class: "flex flex-wrap items-end gap-3",
						"data-testid": "showroom-asset-upload",
						onSubmit: z(I, ["prevent"])
					}, [
						b(i, {
							label: M(c)("showroom.catalog.assets.upload_and_link"),
							error: M(g).error("file")
						}, {
							default: L(() => [v("input", {
								ref: "fileInput",
								type: "file",
								required: "",
								class: "text-sm",
								"data-testid": "showroom-asset-file",
								onChange: E
							}, null, 544)]),
							_: 1
						}, 8, ["label", "error"]),
						b(i, {
							label: M(c)("showroom.catalog.assets.role"),
							error: M(g).error("role")
						}, {
							default: L(() => [b(a, {
								modelValue: M(C),
								"onUpdate:modelValue": n[0] ||= (e) => S(C) ? C.value = e : null,
								class: "w-32",
								"data-testid": "showroom-asset-role"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						b(s, {
							type: "submit",
							icon: "i-lucide-upload",
							loading: M(g).busy.value,
							"data-testid": "showroom-asset-upload-submit"
						}, {
							default: L(() => [y(j(M(c)("showroom.catalog.assets.upload")), 1)]),
							_: 1
						}, 8, ["loading"])
					], 32)
				])]),
				_: 1
			});
		};
	}
}), K = { class: "font-semibold" }, q = { class: "space-y-4" }, J = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-associations-empty"
}, Y = ["data-association-type"], X = { class: "mb-1 font-mono text-xs font-medium uppercase text-muted" }, Z = { class: "flex flex-wrap gap-2" }, re = ["data-testid"], ie = {
	key: 0,
	class: "text-xs text-muted"
}, ae = {
	key: 2,
	class: "text-xs text-muted"
}, oe = {
	value: "",
	disabled: ""
}, se = ["value"], ce = { value: "products" }, le = { value: "product_models" }, ue = /* @__PURE__ */ x({
	__name: "CatalogAssociations",
	props: {
		associations: {},
		quantified: {},
		ownAssociations: {},
		ownQuantified: {},
		types: {},
		save: { type: Function }
	},
	emits: ["changed"],
	setup(t, { emit: i }) {
		let a = t, s = i, { t: c } = B(), x = n(), S = ["products", "product_models"], C = E({
			type: "",
			kind: "products",
			target: "",
			quantity: ""
		}), w = m(() => {
			let e = /* @__PURE__ */ new Map();
			for (let [t, n] of Object.entries(a.associations)) for (let r of S) for (let i of n[r]) {
				let n = a.ownAssociations[t]?.[r].includes(i) ?? !1;
				e.set(t, [...e.get(t) ?? [], {
					type: t,
					kind: r,
					identifier: i,
					quantity: null,
					own: n
				}]);
			}
			for (let [t, n] of Object.entries(a.quantified)) for (let r of S) for (let i of n[r]) {
				let n = a.ownQuantified[t]?.[r].some((e) => e.identifier === i.identifier) ?? !1;
				e.set(t, [...e.get(t) ?? [], {
					type: t,
					kind: r,
					identifier: i.identifier,
					quantity: i.quantity,
					own: n
				}]);
			}
			return [...e.entries()].filter(([, e]) => e.length > 0);
		});
		function D(e) {
			return a.types.find((t) => t.code === e)?.is_quantified ?? e in a.quantified;
		}
		function A(e) {
			return e.kind === "products" ? r(e.identifier) : o(e.identifier);
		}
		function N(e, t) {
			return D(e) ? { quantified_associations: { [e]: t } } : { associations: { [e]: t } };
		}
		async function P(e) {
			await x.run(() => a.save(e)) && (C.target = "", C.quantity = "", s("changed"));
		}
		async function I(e) {
			if (D(e.type)) {
				let t = a.ownQuantified[e.type] ?? {
					products: [],
					product_models: []
				};
				await P(N(e.type, {
					...t,
					[e.kind]: t[e.kind].filter((t) => t.identifier !== e.identifier)
				}));
			} else {
				let t = a.ownAssociations[e.type] ?? {
					products: [],
					product_models: []
				};
				await P(N(e.type, {
					...t,
					[e.kind]: t[e.kind].filter((t) => t !== e.identifier)
				}));
			}
		}
		async function V() {
			let e = C.target.trim();
			if (C.type && e !== "") {
				if (D(C.type)) {
					let t = a.ownQuantified[C.type] ?? {
						products: [],
						product_models: []
					}, n = Number(C.quantity) >= 1 ? Math.trunc(Number(C.quantity)) : 1;
					await P(N(C.type, {
						...t,
						[C.kind]: [...t[C.kind].filter((t) => t.identifier !== e), {
							identifier: e,
							quantity: n
						}]
					}));
				} else {
					let t = a.ownAssociations[C.type] ?? {
						products: [],
						product_models: []
					};
					await P(N(C.type, {
						...t,
						[C.kind]: [...t[C.kind].filter((t) => t !== e), e]
					}));
				}
			}
		}
		return (t, n) => {
			let r = k("RouterLink"), i = l, o = d, s = f, m = u;
			return T(), h(m, { "data-testid": "showroom-associations" }, {
				header: L(() => [v("h2", K, j(M(c)("showroom.catalog.associations.title")), 1)]),
				default: L(() => [v("div", q, [
					M(w).length === 0 ? (T(), _("p", J, j(M(c)("showroom.catalog.associations.none")), 1)) : g("", !0),
					(T(!0), _(p, null, O(M(w), ([e, t]) => (T(), _("div", {
						key: e,
						"data-association-type": e
					}, [v("p", X, j(e), 1), v("ul", Z, [(T(!0), _(p, null, O(t, (t) => (T(), _("li", {
						key: `${t.kind}-${t.identifier}`,
						class: "flex items-center gap-1.5 rounded-md border border-default px-2 py-1 text-sm",
						"data-testid": `showroom-association-${e}-${t.identifier}`
					}, [
						b(r, {
							to: A(t),
							class: "font-mono hover:underline"
						}, {
							default: L(() => [y(j(t.identifier), 1)]),
							_: 2
						}, 1032, ["to"]),
						t.quantity === null ? g("", !0) : (T(), _("span", ie, "× " + j(t.quantity), 1)),
						t.own ? (T(), h(i, {
							key: 1,
							size: "xs",
							variant: "ghost",
							icon: "i-lucide-x",
							"aria-label": M(c)("showroom.catalog.associations.remove"),
							loading: M(x).busy.value,
							"data-testid": `showroom-association-remove-${e}-${t.identifier}`,
							onClick: (e) => I(t)
						}, null, 8, [
							"aria-label",
							"loading",
							"data-testid",
							"onClick"
						])) : (T(), _("span", ae, j(M(c)("showroom.catalog.inherited")), 1))
					], 8, re))), 128))])], 8, Y))), 128)),
					b(e, { action: M(x) }, null, 8, ["action"]),
					a.types.length > 0 ? (T(), _("form", {
						key: 1,
						class: "flex flex-wrap items-end gap-3",
						"data-testid": "showroom-association-add",
						onSubmit: z(V, ["prevent"])
					}, [
						b(o, {
							label: M(c)("showroom.catalog.associations.type"),
							error: M(x).error("associations") ?? M(x).error("quantified_associations")
						}, {
							default: L(() => [R(v("select", {
								"onUpdate:modelValue": n[0] ||= (e) => M(C).type = e,
								required: "",
								class: "w-44 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
								"data-testid": "showroom-association-type"
							}, [v("option", oe, j(M(c)("showroom.catalog.associations.choose_type")), 1), (T(!0), _(p, null, O(a.types, (e) => (T(), _("option", {
								key: e.code,
								value: e.code
							}, j(e.code), 9, se))), 128))], 512), [[F, M(C).type]])]),
							_: 1
						}, 8, ["label", "error"]),
						b(o, { label: M(c)("showroom.catalog.associations.target_kind") }, {
							default: L(() => [R(v("select", {
								"onUpdate:modelValue": n[1] ||= (e) => M(C).kind = e,
								class: "w-40 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
								"data-testid": "showroom-association-kind"
							}, [v("option", ce, j(M(c)("showroom.catalog.associations.product")), 1), v("option", le, j(M(c)("showroom.catalog.associations.product_model")), 1)], 512), [[F, M(C).kind]])]),
							_: 1
						}, 8, ["label"]),
						b(o, { label: M(c)("showroom.catalog.associations.target") }, {
							default: L(() => [b(s, {
								modelValue: M(C).target,
								"onUpdate:modelValue": n[2] ||= (e) => M(C).target = e,
								required: "",
								class: "w-44",
								"data-testid": "showroom-association-target"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						b(o, {
							label: M(c)("showroom.catalog.associations.quantity"),
							hint: M(c)("showroom.catalog.associations.quantity_hint")
						}, {
							default: L(() => [b(s, {
								modelValue: M(C).quantity,
								"onUpdate:modelValue": n[3] ||= (e) => M(C).quantity = e,
								type: "number",
								min: "1",
								class: "w-28",
								"data-testid": "showroom-association-quantity"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "hint"]),
						b(i, {
							type: "submit",
							icon: "i-lucide-plus",
							loading: M(x).busy.value,
							"data-testid": "showroom-association-add-submit"
						}, {
							default: L(() => [y(j(M(c)("showroom.catalog.associations.add")), 1)]),
							_: 1
						}, 8, ["loading"])
					], 32)) : g("", !0)
				])]),
				_: 1
			});
		};
	}
}), de = { class: "font-semibold" }, fe = { class: "grid gap-x-4 gap-y-2 text-sm sm:grid-cols-[auto_1fr]" }, pe = { class: "font-mono text-xs text-muted" }, me = ["data-testid"], he = /* @__PURE__ */ x({
	__name: "InheritedValues",
	props: { values: {} },
	setup(e) {
		let t = e, { t: n } = B(), r = m(() => Object.entries(t.values).flatMap(([e, t]) => t.map((t) => ({
			key: [
				e,
				t.locale,
				t.scope
			].join("|"),
			term: [
				e,
				t.locale,
				t.scope
			].filter((e) => e).join(" · "),
			data: t.data
		}))));
		function i(e) {
			return typeof e == "boolean" ? n(e ? "showroom.catalog.yes" : "showroom.catalog.no") : e == null ? n("showroom.catalog.none") : typeof e == "object" ? JSON.stringify(e) : String(e);
		}
		return (e, t) => {
			let a = u;
			return M(r).length > 0 ? (T(), h(a, {
				key: 0,
				"data-testid": "showroom-inherited-values"
			}, {
				header: L(() => [v("h2", de, j(M(n)("showroom.catalog.values.inherited")), 1)]),
				default: L(() => [v("dl", fe, [(T(!0), _(p, null, O(M(r), (e) => (T(), _(p, { key: e.key }, [v("dt", pe, j(e.term), 1), v("dd", { "data-testid": `showroom-inherited-${e.key}` }, j(i(e.data)), 9, me)], 64))), 128))])]),
				_: 1
			})) : g("", !0);
		};
	}
}), ge = ["value"], _e = ["value"], ve = /* @__PURE__ */ x({
	__name: "SlotPicker",
	props: {
		locales: {},
		channels: {},
		editing: {}
	},
	emits: ["pick"],
	setup(e, { emit: t }) {
		let n = e, r = t, { t: a, locale: o } = B(), s = E({
			locale: "",
			channel: ""
		});
		I(() => n.editing, (e) => {
			s.locale = e.locale ?? "", s.channel = e.channel ?? "";
		}, { immediate: !0 });
		function c() {
			r("pick", {
				locale: s.locale || null,
				channel: s.channel || null
			});
		}
		return (e, t) => {
			let r = d, f = l, m = u;
			return n.locales.length > 0 || n.channels.length > 0 ? (T(), h(m, { key: 0 }, {
				default: L(() => [v("form", {
					class: "flex flex-wrap items-end gap-3",
					"data-testid": "showroom-slot-picker",
					onSubmit: z(c, ["prevent"])
				}, [
					n.locales.length > 0 ? (T(), h(r, {
						key: 0,
						label: M(a)("showroom.catalog.fields.locale")
					}, {
						default: L(() => [R(v("select", {
							"onUpdate:modelValue": t[0] ||= (e) => M(s).locale = e,
							class: "w-44 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
							"data-testid": "showroom-slot-locale"
						}, [(T(!0), _(p, null, O(n.locales, (e) => (T(), _("option", {
							key: e.code,
							value: e.code
						}, j(M(i)(e.labels, M(o), e.code)), 9, ge))), 128))], 512), [[F, M(s).locale]])]),
						_: 1
					}, 8, ["label"])) : g("", !0),
					n.channels.length > 0 ? (T(), h(r, {
						key: 1,
						label: M(a)("showroom.catalog.fields.channel")
					}, {
						default: L(() => [R(v("select", {
							"onUpdate:modelValue": t[1] ||= (e) => M(s).channel = e,
							class: "w-44 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
							"data-testid": "showroom-slot-channel"
						}, [(T(!0), _(p, null, O(n.channels, (e) => (T(), _("option", {
							key: e.code,
							value: e.code
						}, j(M(i)(e.labels, M(o), e.code)), 9, _e))), 128))], 512), [[F, M(s).channel]])]),
						_: 1
					}, 8, ["label"])) : g("", !0),
					b(f, {
						type: "submit",
						variant: "outline",
						icon: "i-lucide-arrow-left-right",
						"data-testid": "showroom-slot-switch"
					}, {
						default: L(() => [y(j(M(a)("showroom.catalog.values.switch")), 1)]),
						_: 1
					})
				], 32)]),
				_: 1
			})) : g("", !0);
		};
	}
}), ye = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-values-empty"
}, be = {
	key: 1,
	class: "grid gap-4 sm:grid-cols-2",
	"data-testid": "showroom-values"
}, xe = ["data-attribute"], Se = ["for"], Ce = { class: "font-mono text-xs font-normal text-muted" }, we = {
	key: 0,
	class: "text-xs text-muted"
}, Te = [
	"id",
	"disabled",
	"value",
	"data-testid",
	"onInput"
], Ee = [
	"id",
	"step",
	"disabled",
	"value",
	"data-testid",
	"onInput"
], De = [
	"id",
	"disabled",
	"value",
	"data-testid",
	"onInput"
], Oe = [
	"id",
	"disabled",
	"value",
	"data-testid",
	"onChange"
], ke = { value: "" }, Ae = { value: "1" }, je = { value: "0" }, Me = [
	"id",
	"disabled",
	"value",
	"data-testid",
	"onChange"
], Ne = { value: "" }, Pe = ["value"], Fe = {
	key: 5,
	class: "flex flex-wrap gap-3"
}, Q = [
	"value",
	"disabled",
	"checked",
	"data-testid",
	"onChange"
], Ie = {
	key: 6,
	class: "grid grid-cols-2 gap-2"
}, Le = [
	"id",
	"aria-label",
	"disabled",
	"value",
	"data-testid",
	"onInput"
], Re = [
	"aria-label",
	"disabled",
	"value",
	"data-testid",
	"onInput"
], ze = {
	key: 7,
	class: "flex flex-wrap gap-2"
}, Be = { class: "font-mono text-xs" }, Ve = [
	"disabled",
	"value",
	"data-testid",
	"onInput"
], He = [
	"id",
	"disabled",
	"value",
	"data-testid",
	"onInput"
], Ue = ["data-testid"], $ = "w-full rounded-md border border-default bg-default px-2.5 py-1.5 text-sm", We = /* @__PURE__ */ x({
	__name: "ValueEditor",
	props: /*@__PURE__*/ C({
		attributes: {},
		editing: {},
		error: { type: Function },
		disabled: { type: Boolean }
	}, {
		modelValue: { required: !0 },
		modelModifiers: {}
	}),
	emits: ["update:modelValue"],
	setup(e) {
		let t = e, n = N(e, "modelValue"), { t: r, locale: a } = B();
		function o(e) {
			let t = n.value[e];
			return typeof t == "string" ? t : "";
		}
		function l(e, t) {
			n.value[e] = t;
		}
		function u(e) {
			let t = n.value[e];
			return Array.isArray(t) ? t : [];
		}
		function d(e, t, r) {
			let i = u(e).filter((e) => e !== t);
			n.value[e] = r ? [...i, t] : i;
		}
		function f(e) {
			let t = n.value[e];
			return t && typeof t == "object" && !Array.isArray(t) && "amount" in t ? t : {
				amount: "",
				unit: ""
			};
		}
		function m(e, t, r) {
			n.value[e] = {
				...f(e),
				[t]: r
			};
		}
		function h(e) {
			let t = n.value[e];
			return t && typeof t == "object" && !Array.isArray(t) && !("amount" in t) ? t : {};
		}
		function b(e, t, r) {
			n.value[e] = {
				...h(e),
				[t]: r
			};
		}
		function x(e) {
			return e.target.value;
		}
		function S(e) {
			return e.target.checked;
		}
		function C(e) {
			return [
				e.code,
				e.type,
				e.is_localizable ? t.editing.locale : null,
				e.is_scopable ? t.editing.channel : null
			].filter((e) => e).join(" · ");
		}
		return (e, E) => t.attributes.length === 0 ? (T(), _("p", ye, j(M(r)("showroom.catalog.values.none_settable")), 1)) : (T(), _("div", be, [(T(!0), _(p, null, O(t.attributes, (e) => (T(), _("div", {
			key: e.code,
			class: "flex flex-col gap-1.5",
			"data-attribute": e.code
		}, [v("label", {
			class: "text-sm font-medium",
			for: `showroom-value-${e.code}`
		}, [y(j(M(i)(e.labels, M(a), e.code)) + " ", 1), v("span", Ce, j(C(e)), 1)], 8, Se), M(s)(e, t.editing) ? (T(), _(p, { key: 1 }, [e.type === "textarea" ? (T(), _("textarea", {
			key: 0,
			id: `showroom-value-${e.code}`,
			class: w($),
			rows: "3",
			disabled: t.disabled,
			value: o(e.code),
			"data-testid": `showroom-value-${e.code}`,
			onInput: (t) => l(e.code, x(t))
		}, null, 40, Te)) : e.type === "number" || e.type === "decimal" ? (T(), _("input", {
			key: 1,
			id: `showroom-value-${e.code}`,
			type: "number",
			step: e.type === "number" ? "1" : "any",
			class: w($),
			disabled: t.disabled,
			value: o(e.code),
			"data-testid": `showroom-value-${e.code}`,
			onInput: (t) => l(e.code, x(t))
		}, null, 40, Ee)) : e.type === "date" ? (T(), _("input", {
			key: 2,
			id: `showroom-value-${e.code}`,
			type: "date",
			class: w($),
			disabled: t.disabled,
			value: o(e.code),
			"data-testid": `showroom-value-${e.code}`,
			onInput: (t) => l(e.code, x(t))
		}, null, 40, De)) : e.type === "boolean" ? (T(), _("select", {
			key: 3,
			id: `showroom-value-${e.code}`,
			class: w($),
			disabled: t.disabled,
			value: o(e.code),
			"data-testid": `showroom-value-${e.code}`,
			onChange: (t) => l(e.code, x(t))
		}, [
			v("option", ke, j(M(r)("showroom.catalog.none")), 1),
			v("option", Ae, j(M(r)("showroom.catalog.yes")), 1),
			v("option", je, j(M(r)("showroom.catalog.no")), 1)
		], 40, Oe)) : e.type === "select" ? (T(), _("select", {
			key: 4,
			id: `showroom-value-${e.code}`,
			class: w($),
			disabled: t.disabled,
			value: o(e.code),
			"data-testid": `showroom-value-${e.code}`,
			onChange: (t) => l(e.code, x(t))
		}, [v("option", Ne, j(M(r)("showroom.catalog.none")), 1), (T(!0), _(p, null, O(e.options ?? [], (e) => (T(), _("option", {
			key: e.code,
			value: e.code
		}, j(M(i)(e.labels, M(a), e.code)), 9, Pe))), 128))], 40, Me)) : e.type === "multiselect" ? (T(), _("div", Fe, [(T(!0), _(p, null, O(e.options ?? [], (n) => (T(), _("label", {
			key: n.code,
			class: "flex items-center gap-1.5 text-sm"
		}, [v("input", {
			type: "checkbox",
			value: n.code,
			disabled: t.disabled,
			checked: u(e.code).includes(n.code),
			"data-testid": `showroom-value-${e.code}-${n.code}`,
			onChange: (t) => d(e.code, n.code, S(t))
		}, null, 40, Q), y(" " + j(M(i)(n.labels, M(a), n.code)), 1)]))), 128))])) : e.type === "metric" ? (T(), _("div", Ie, [v("input", {
			id: `showroom-value-${e.code}`,
			type: "number",
			step: "any",
			class: w($),
			"aria-label": M(r)("showroom.catalog.values.amount"),
			disabled: t.disabled,
			value: f(e.code).amount,
			"data-testid": `showroom-value-${e.code}-amount`,
			onInput: (t) => m(e.code, "amount", x(t))
		}, null, 40, Le), v("input", {
			class: w($),
			"aria-label": M(r)("showroom.catalog.values.unit"),
			disabled: t.disabled,
			value: f(e.code).unit,
			"data-testid": `showroom-value-${e.code}-unit`,
			onInput: (t) => m(e.code, "unit", x(t))
		}, null, 40, Re)])) : e.type === "price" ? (T(), _("div", ze, [(T(!0), _(p, null, O(M(c)(e, n.value[e.code]), (n) => (T(), _("label", {
			key: n,
			class: "flex items-center gap-1.5"
		}, [v("span", Be, j(n), 1), v("input", {
			type: "number",
			step: "any",
			class: "w-32 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
			disabled: t.disabled,
			value: h(e.code)[n] ?? "",
			"data-testid": `showroom-value-${e.code}-${n}`,
			onInput: (t) => b(e.code, n, x(t))
		}, null, 40, Ve)]))), 128))])) : (T(), _("input", {
			key: 8,
			id: `showroom-value-${e.code}`,
			class: w($),
			disabled: t.disabled,
			value: o(e.code),
			"data-testid": `showroom-value-${e.code}`,
			onInput: (t) => l(e.code, x(t))
		}, null, 40, He)), t.error(`values.${e.code}`) ? (T(), _("small", {
			key: 9,
			class: "text-xs text-error",
			"data-testid": `showroom-value-error-${e.code}`
		}, j(t.error(`values.${e.code}`)), 9, Ue)) : g("", !0)], 64)) : (T(), _("p", we, j(M(r)("showroom.catalog.values.slot_unavailable")), 1))], 8, xe))), 128))]));
	}
});
export { G as a, ue as i, ve as n, he as r, We as t };
