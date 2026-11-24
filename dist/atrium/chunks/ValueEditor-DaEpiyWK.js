import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { C as t, O as n, b as r, f as i, j as a, v as o } from "./catalog-DCoO3A8a.js";
import { o as s, r as c } from "./values-B4RtDnRM.js";
import { AtriumDetails as l, AtriumSelect as u, AtriumState as d } from "@refactor-circus/atrium";
import { UButton as f, UCard as p, UCheckbox as m, UFormField as h, UInput as g, ULink as _, USelect as v, UTextarea as y } from "@refactor-circus/atrium/ui";
import { Fragment as b, computed as x, createBlock as S, createCommentVNode as C, createElementBlock as w, createElementVNode as T, createTextVNode as E, createVNode as D, defineComponent as O, isRef as k, mergeModels as A, openBlock as j, reactive as M, ref as N, renderList as P, shallowRef as F, toDisplayString as I, unref as L, useModel as R, useTemplateRef as z, watch as B, withCtx as V, withModifiers as H } from "vue";
import { useI18n as U } from "vue-i18n";
// src/components/catalog/CatalogAssets.vue?vue&type=script&setup=true&lang.ts
var ee = { class: "font-semibold" }, W = { class: "space-y-4" }, G = {
	key: 1,
	class: "grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6"
}, K = ["src", "alt"], q = {
	key: 1,
	class: "flex aspect-square items-center justify-center rounded bg-elevated font-mono text-xs text-muted"
}, J = { class: "truncate text-xs" }, Y = /* @__PURE__ */ O({
	__name: "CatalogAssets",
	props: {
		assets: {},
		linkType: {},
		linkTarget: {}
	},
	emits: ["changed"],
	setup(r, { emit: i }) {
		let o = r, s = i, { t: c } = U(), l = a(), u = n(), m = F(null), v = N("image"), y = z("fileInput");
		function x(e) {
			m.value = e.target.files?.[0] ?? null;
		}
		function C(e) {
			return !!e.url && (e.mime_type ?? "").startsWith("image/");
		}
		function O(e) {
			return [e.role, e.inherited ? c("showroom.catalog.inherited") : e.code].filter((e) => e).join(" · ");
		}
		async function A() {
			let e = m.value;
			l && e && await u.run(async () => {
				let n = new FormData();
				n.append("file", e);
				let r = t(await l.POST("/assets", {
					body: n,
					bodySerializer: (e) => e
				})).data;
				t(await l.POST("/assets/{asset}/links", {
					params: { path: { asset: r.code } },
					body: {
						type: o.linkType,
						target: o.linkTarget,
						...v.value.trim() ? { role: v.value.trim() } : {}
					}
				}));
			}) && (m.value = null, y.value?.inputRef && (y.value.inputRef.value = ""), s("changed"));
		}
		return (t, n) => {
			let r = d, i = _, a = g, s = h, l = f, m = p;
			return j(), S(m, { "data-testid": "showroom-assets" }, {
				header: V(() => [T("h2", ee, I(L(c)("showroom.catalog.assets.title")), 1)]),
				default: V(() => [T("div", W, [
					o.assets.length === 0 ? (j(), S(r, {
						key: 0,
						compact: "",
						title: L(c)("showroom.catalog.assets.none"),
						"data-testid": "showroom-assets-empty"
					}, null, 8, ["title"])) : (j(), w("div", G, [(j(!0), w(b, null, P(o.assets, (e) => (j(), S(i, {
						key: `${e.inherited ? "inherited" : "own"}-${e.code}`,
						to: `/showroom/assets/${encodeURIComponent(e.code)}`,
						class: "flex flex-col gap-2 rounded-md border border-default p-2 hover:border-primary",
						"data-testid": `showroom-asset-${e.code}`
					}, {
						default: V(() => [C(e) ? (j(), w("img", {
							key: 0,
							src: e.url ?? void 0,
							alt: e.code,
							class: "aspect-square w-full rounded object-cover"
						}, null, 8, K)) : (j(), w("span", q, I(e.mime_type ?? e.code), 1)), T("span", J, I(O(e)), 1)]),
						_: 2
					}, 1032, ["to", "data-testid"]))), 128))])),
					D(e, { action: L(u) }, null, 8, ["action"]),
					T("form", {
						class: "flex flex-wrap items-end gap-3",
						"data-testid": "showroom-asset-upload",
						onSubmit: H(A, ["prevent"])
					}, [
						D(s, {
							label: L(c)("showroom.catalog.assets.upload_and_link"),
							error: L(u).error("file")
						}, {
							default: V(() => [D(a, {
								ref: "fileInput",
								type: "file",
								required: "",
								"data-testid": "showroom-asset-file",
								onChange: x
							}, null, 512)]),
							_: 1
						}, 8, ["label", "error"]),
						D(s, {
							label: L(c)("showroom.catalog.assets.role"),
							error: L(u).error("role")
						}, {
							default: V(() => [D(a, {
								modelValue: L(v),
								"onUpdate:modelValue": n[0] ||= (e) => k(v) ? v.value = e : null,
								class: "w-32",
								"data-testid": "showroom-asset-role"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						D(l, {
							type: "submit",
							icon: "i-lucide-upload",
							loading: L(u).busy.value,
							"data-testid": "showroom-asset-upload-submit"
						}, {
							default: V(() => [E(I(L(c)("showroom.catalog.assets.upload")), 1)]),
							_: 1
						}, 8, ["loading"])
					], 32)
				])]),
				_: 1
			});
		};
	}
}), X = { class: "font-semibold" }, Z = { class: "space-y-4" }, Q = ["data-association-type"], te = { class: "mb-1 font-mono text-xs font-medium uppercase text-muted" }, ne = { class: "flex flex-wrap gap-2" }, re = ["data-testid"], ie = {
	key: 0,
	class: "text-xs text-muted"
}, ae = {
	key: 2,
	class: "text-xs text-muted"
}, oe = /* @__PURE__ */ O({
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
		let a = t, s = i, { t: c } = U(), l = n(), u = ["products", "product_models"], m = M({
			type: "",
			kind: "products",
			target: "",
			quantity: ""
		}), y = x(() => a.types.map((e) => ({
			label: e.code,
			value: e.code
		}))), O = x(() => [{
			label: c("showroom.catalog.associations.product"),
			value: "products"
		}, {
			label: c("showroom.catalog.associations.product_model"),
			value: "product_models"
		}]), k = x(() => {
			let e = /* @__PURE__ */ new Map();
			for (let [t, n] of Object.entries(a.associations)) for (let r of u) for (let i of n[r]) {
				let n = a.ownAssociations[t]?.[r].includes(i) ?? !1;
				e.set(t, [...e.get(t) ?? [], {
					type: t,
					kind: r,
					identifier: i,
					quantity: null,
					own: n
				}]);
			}
			for (let [t, n] of Object.entries(a.quantified)) for (let r of u) for (let i of n[r]) {
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
		function A(e) {
			return a.types.find((t) => t.code === e)?.is_quantified ?? e in a.quantified;
		}
		function N(e) {
			return e.kind === "products" ? r(e.identifier) : o(e.identifier);
		}
		function F(e, t) {
			return A(e) ? { quantified_associations: { [e]: t } } : { associations: { [e]: t } };
		}
		async function R(e) {
			await l.run(() => a.save(e)) && (m.target = "", m.quantity = "", s("changed"));
		}
		async function z(e) {
			if (A(e.type)) {
				let t = a.ownQuantified[e.type] ?? {
					products: [],
					product_models: []
				};
				await R(F(e.type, {
					...t,
					[e.kind]: t[e.kind].filter((t) => t.identifier !== e.identifier)
				}));
			} else {
				let t = a.ownAssociations[e.type] ?? {
					products: [],
					product_models: []
				};
				await R(F(e.type, {
					...t,
					[e.kind]: t[e.kind].filter((t) => t !== e.identifier)
				}));
			}
		}
		async function B() {
			let e = m.target.trim();
			if (m.type && e !== "") {
				if (A(m.type)) {
					let t = a.ownQuantified[m.type] ?? {
						products: [],
						product_models: []
					}, n = Number(m.quantity) >= 1 ? Math.trunc(Number(m.quantity)) : 1;
					await R(F(m.type, {
						...t,
						[m.kind]: [...t[m.kind].filter((t) => t.identifier !== e), {
							identifier: e,
							quantity: n
						}]
					}));
				} else {
					let t = a.ownAssociations[m.type] ?? {
						products: [],
						product_models: []
					};
					await R(F(m.type, {
						...t,
						[m.kind]: [...t[m.kind].filter((t) => t !== e), e]
					}));
				}
			}
		}
		return (t, n) => {
			let r = d, i = _, o = f, s = v, u = h, x = g, A = p;
			return j(), S(A, { "data-testid": "showroom-associations" }, {
				header: V(() => [T("h2", X, I(L(c)("showroom.catalog.associations.title")), 1)]),
				default: V(() => [T("div", Z, [
					L(k).length === 0 ? (j(), S(r, {
						key: 0,
						compact: "",
						title: L(c)("showroom.catalog.associations.none"),
						"data-testid": "showroom-associations-empty"
					}, null, 8, ["title"])) : C("", !0),
					(j(!0), w(b, null, P(L(k), ([e, t]) => (j(), w("div", {
						key: e,
						"data-association-type": e
					}, [T("p", te, I(e), 1), T("ul", ne, [(j(!0), w(b, null, P(t, (t) => (j(), w("li", {
						key: `${t.kind}-${t.identifier}`,
						class: "flex items-center gap-1.5 rounded-md border border-default px-2 py-1 text-sm",
						"data-testid": `showroom-association-${e}-${t.identifier}`
					}, [
						D(i, {
							to: N(t),
							class: "font-mono hover:underline"
						}, {
							default: V(() => [E(I(t.identifier), 1)]),
							_: 2
						}, 1032, ["to"]),
						t.quantity === null ? C("", !0) : (j(), w("span", ie, "× " + I(t.quantity), 1)),
						t.own ? (j(), S(o, {
							key: 1,
							size: "xs",
							variant: "ghost",
							icon: "i-lucide-x",
							"aria-label": L(c)("showroom.catalog.associations.remove"),
							loading: L(l).busy.value,
							"data-testid": `showroom-association-remove-${e}-${t.identifier}`,
							onClick: (e) => z(t)
						}, null, 8, [
							"aria-label",
							"loading",
							"data-testid",
							"onClick"
						])) : (j(), w("span", ae, I(L(c)("showroom.catalog.inherited")), 1))
					], 8, re))), 128))])], 8, Q))), 128)),
					D(e, { action: L(l) }, null, 8, ["action"]),
					a.types.length > 0 ? (j(), w("form", {
						key: 1,
						class: "flex flex-wrap items-end gap-3",
						"data-testid": "showroom-association-add",
						onSubmit: H(B, ["prevent"])
					}, [
						D(u, {
							label: L(c)("showroom.catalog.associations.type"),
							error: L(l).error("associations") ?? L(l).error("quantified_associations")
						}, {
							default: V(() => [D(s, {
								modelValue: L(m).type,
								"onUpdate:modelValue": n[0] ||= (e) => L(m).type = e,
								items: L(y),
								placeholder: L(c)("showroom.catalog.associations.choose_type"),
								required: "",
								class: "w-44",
								"data-testid": "showroom-association-type"
							}, null, 8, [
								"modelValue",
								"items",
								"placeholder"
							])]),
							_: 1
						}, 8, ["label", "error"]),
						D(u, { label: L(c)("showroom.catalog.associations.target_kind") }, {
							default: V(() => [D(s, {
								modelValue: L(m).kind,
								"onUpdate:modelValue": n[1] ||= (e) => L(m).kind = e,
								items: L(O),
								class: "w-40",
								"data-testid": "showroom-association-kind"
							}, null, 8, ["modelValue", "items"])]),
							_: 1
						}, 8, ["label"]),
						D(u, { label: L(c)("showroom.catalog.associations.target") }, {
							default: V(() => [D(x, {
								modelValue: L(m).target,
								"onUpdate:modelValue": n[2] ||= (e) => L(m).target = e,
								required: "",
								class: "w-44",
								"data-testid": "showroom-association-target"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						D(u, {
							label: L(c)("showroom.catalog.associations.quantity"),
							hint: L(c)("showroom.catalog.associations.quantity_hint")
						}, {
							default: V(() => [D(x, {
								modelValue: L(m).quantity,
								"onUpdate:modelValue": n[3] ||= (e) => L(m).quantity = e,
								type: "number",
								min: "1",
								class: "w-28",
								"data-testid": "showroom-association-quantity"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "hint"]),
						D(o, {
							type: "submit",
							icon: "i-lucide-plus",
							loading: L(l).busy.value,
							"data-testid": "showroom-association-add-submit"
						}, {
							default: V(() => [E(I(L(c)("showroom.catalog.associations.add")), 1)]),
							_: 1
						}, 8, ["loading"])
					], 32)) : C("", !0)
				])]),
				_: 1
			});
		};
	}
}), se = { class: "font-semibold" }, ce = /* @__PURE__ */ O({
	__name: "InheritedValues",
	props: { values: {} },
	setup(e) {
		let t = e, { t: n } = U();
		function r(e) {
			return typeof e == "boolean" ? n(e ? "showroom.catalog.yes" : "showroom.catalog.no") : e == null ? n("showroom.catalog.none") : typeof e == "object" ? JSON.stringify(e) : String(e);
		}
		let i = x(() => Object.entries(t.values).flatMap(([e, t]) => t.map((t) => {
			let n = [
				e,
				t.locale,
				t.scope
			].join("|");
			return {
				key: n,
				label: [
					e,
					t.locale,
					t.scope
				].filter((e) => e).join(" · "),
				value: r(t.data),
				testid: `showroom-inherited-${n}`
			};
		})));
		return (e, t) => {
			let r = l, a = p;
			return L(i).length > 0 ? (j(), S(a, {
				key: 0,
				"data-testid": "showroom-inherited-values"
			}, {
				header: V(() => [T("h2", se, I(L(n)("showroom.catalog.values.inherited")), 1)]),
				default: V(() => [D(r, { items: L(i) }, null, 8, ["items"])]),
				_: 1
			})) : C("", !0);
		};
	}
}), le = /* @__PURE__ */ O({
	__name: "SlotPicker",
	props: {
		locales: {},
		channels: {},
		editing: {}
	},
	emits: ["pick"],
	setup(e, { emit: t }) {
		let n = e, r = t, { t: a, locale: o } = U(), s = M({
			locale: "",
			channel: ""
		});
		B(() => n.editing, (e) => {
			s.locale = e.locale ?? "", s.channel = e.channel ?? "";
		}, { immediate: !0 });
		let c = x(() => n.locales.map((e) => ({
			label: i(e.labels, o.value, e.code),
			value: e.code
		}))), l = x(() => n.channels.map((e) => ({
			label: i(e.labels, o.value, e.code),
			value: e.code
		})));
		function u() {
			r("pick", {
				locale: s.locale || null,
				channel: s.channel || null
			});
		}
		return (e, t) => {
			let r = v, i = h, o = f, d = p;
			return n.locales.length > 0 || n.channels.length > 0 ? (j(), S(d, { key: 0 }, {
				default: V(() => [T("form", {
					class: "flex flex-wrap items-end gap-3",
					"data-testid": "showroom-slot-picker",
					onSubmit: H(u, ["prevent"])
				}, [
					n.locales.length > 0 ? (j(), S(i, {
						key: 0,
						label: L(a)("showroom.catalog.fields.locale")
					}, {
						default: V(() => [D(r, {
							modelValue: L(s).locale,
							"onUpdate:modelValue": t[0] ||= (e) => L(s).locale = e,
							items: L(c),
							class: "w-44",
							"data-testid": "showroom-slot-locale"
						}, null, 8, ["modelValue", "items"])]),
						_: 1
					}, 8, ["label"])) : C("", !0),
					n.channels.length > 0 ? (j(), S(i, {
						key: 1,
						label: L(a)("showroom.catalog.fields.channel")
					}, {
						default: V(() => [D(r, {
							modelValue: L(s).channel,
							"onUpdate:modelValue": t[1] ||= (e) => L(s).channel = e,
							items: L(l),
							class: "w-44",
							"data-testid": "showroom-slot-channel"
						}, null, 8, ["modelValue", "items"])]),
						_: 1
					}, 8, ["label"])) : C("", !0),
					D(o, {
						type: "submit",
						variant: "outline",
						icon: "i-lucide-arrow-left-right",
						"data-testid": "showroom-slot-switch"
					}, {
						default: V(() => [E(I(L(a)("showroom.catalog.values.switch")), 1)]),
						_: 1
					})
				], 32)]),
				_: 1
			})) : C("", !0);
		};
	}
}), ue = {
	key: 1,
	class: "grid gap-4 sm:grid-cols-2",
	"data-testid": "showroom-values"
}, de = ["data-attribute"], fe = ["for"], $ = { class: "font-mono text-xs font-normal text-muted" }, pe = {
	key: 0,
	class: "text-xs text-muted"
}, me = {
	key: 4,
	class: "flex flex-wrap gap-3"
}, he = {
	key: 5,
	class: "grid grid-cols-2 gap-2"
}, ge = {
	key: 6,
	class: "flex flex-wrap gap-2"
}, _e = { class: "font-mono text-xs" }, ve = ["data-testid"], ye = /* @__PURE__ */ O({
	__name: "ValueEditor",
	props: /*@__PURE__*/ A({
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
		let t = e, n = R(e, "modelValue"), { t: r, locale: a } = U();
		function o(e) {
			let t = n.value[e];
			return typeof t == "string" ? t : "";
		}
		function l(e, t) {
			n.value[e] = t;
		}
		function f(e) {
			let t = n.value[e];
			return Array.isArray(t) ? t : [];
		}
		function p(e, t, r) {
			let i = f(e).filter((e) => e !== t);
			n.value[e] = r ? [...i, t] : i;
		}
		function h(e) {
			let t = n.value[e];
			return t && typeof t == "object" && !Array.isArray(t) && "amount" in t ? t : {
				amount: "",
				unit: ""
			};
		}
		function _(e, t, r) {
			n.value[e] = {
				...h(e),
				[t]: r
			};
		}
		function v(e) {
			let t = n.value[e];
			return t && typeof t == "object" && !Array.isArray(t) && !("amount" in t) ? t : {};
		}
		function x(e, t, r) {
			n.value[e] = {
				...v(e),
				[t]: r
			};
		}
		function O(e) {
			return e == null ? "" : String(e);
		}
		function k(e) {
			return e.type === "boolean" ? [{
				label: r("showroom.catalog.yes"),
				value: "1"
			}, {
				label: r("showroom.catalog.no"),
				value: "0"
			}] : (e.options ?? []).map((e) => ({
				label: i(e.labels, a.value, e.code),
				value: e.code
			}));
		}
		function A(e) {
			return [
				e.code,
				e.type,
				e.is_localizable ? t.editing.locale : null,
				e.is_scopable ? t.editing.channel : null
			].filter((e) => e).join(" · ");
		}
		return (e, M) => {
			let N = d, F = y, R = g, z = u, B = m;
			return t.attributes.length === 0 ? (j(), S(N, {
				key: 0,
				compact: "",
				title: L(r)("showroom.catalog.values.none_settable"),
				"data-testid": "showroom-values-empty"
			}, null, 8, ["title"])) : (j(), w("div", ue, [(j(!0), w(b, null, P(t.attributes, (e) => (j(), w("div", {
				key: e.code,
				class: "flex flex-col gap-1.5",
				"data-attribute": e.code
			}, [T("label", {
				class: "text-sm font-medium",
				for: `showroom-value-${e.code}`
			}, [E(I(L(i)(e.labels, L(a), e.code)) + " ", 1), T("span", $, I(A(e)), 1)], 8, fe), L(s)(e, t.editing) ? (j(), w(b, { key: 1 }, [e.type === "textarea" ? (j(), S(F, {
				key: 0,
				id: `showroom-value-${e.code}`,
				class: "w-full",
				rows: 3,
				disabled: t.disabled,
				"model-value": o(e.code),
				"data-testid": `showroom-value-${e.code}`,
				"onUpdate:modelValue": (t) => l(e.code, O(t))
			}, null, 8, [
				"id",
				"disabled",
				"model-value",
				"data-testid",
				"onUpdate:modelValue"
			])) : e.type === "number" || e.type === "decimal" ? (j(), S(R, {
				key: 1,
				id: `showroom-value-${e.code}`,
				type: "number",
				step: e.type === "number" ? "1" : "any",
				class: "w-full",
				disabled: t.disabled,
				"model-value": o(e.code),
				"data-testid": `showroom-value-${e.code}`,
				"onUpdate:modelValue": (t) => l(e.code, O(t))
			}, null, 8, [
				"id",
				"step",
				"disabled",
				"model-value",
				"data-testid",
				"onUpdate:modelValue"
			])) : e.type === "date" ? (j(), S(R, {
				key: 2,
				id: `showroom-value-${e.code}`,
				type: "date",
				class: "w-full",
				disabled: t.disabled,
				"model-value": o(e.code),
				"data-testid": `showroom-value-${e.code}`,
				"onUpdate:modelValue": (t) => l(e.code, O(t))
			}, null, 8, [
				"id",
				"disabled",
				"model-value",
				"data-testid",
				"onUpdate:modelValue"
			])) : e.type === "boolean" || e.type === "select" ? (j(), S(z, {
				key: 3,
				id: `showroom-value-${e.code}`,
				class: "w-full",
				items: k(e),
				blank: L(r)("showroom.catalog.none"),
				disabled: t.disabled,
				"model-value": o(e.code),
				"data-testid": `showroom-value-${e.code}`,
				"onUpdate:modelValue": (t) => l(e.code, O(t))
			}, null, 8, [
				"id",
				"items",
				"blank",
				"disabled",
				"model-value",
				"data-testid",
				"onUpdate:modelValue"
			])) : e.type === "multiselect" ? (j(), w("div", me, [(j(!0), w(b, null, P(e.options ?? [], (n) => (j(), S(B, {
				key: n.code,
				label: L(i)(n.labels, L(a), n.code),
				disabled: t.disabled,
				"model-value": f(e.code).includes(n.code),
				"data-testid": `showroom-value-${e.code}-${n.code}`,
				"onUpdate:modelValue": (t) => p(e.code, n.code, t === !0)
			}, null, 8, [
				"label",
				"disabled",
				"model-value",
				"data-testid",
				"onUpdate:modelValue"
			]))), 128))])) : e.type === "metric" ? (j(), w("div", he, [D(R, {
				id: `showroom-value-${e.code}`,
				type: "number",
				step: "any",
				"aria-label": L(r)("showroom.catalog.values.amount"),
				disabled: t.disabled,
				"model-value": h(e.code).amount,
				"data-testid": `showroom-value-${e.code}-amount`,
				"onUpdate:modelValue": (t) => _(e.code, "amount", O(t))
			}, null, 8, [
				"id",
				"aria-label",
				"disabled",
				"model-value",
				"data-testid",
				"onUpdate:modelValue"
			]), D(R, {
				"aria-label": L(r)("showroom.catalog.values.unit"),
				disabled: t.disabled,
				"model-value": h(e.code).unit,
				"data-testid": `showroom-value-${e.code}-unit`,
				"onUpdate:modelValue": (t) => _(e.code, "unit", O(t))
			}, null, 8, [
				"aria-label",
				"disabled",
				"model-value",
				"data-testid",
				"onUpdate:modelValue"
			])])) : e.type === "price" ? (j(), w("div", ge, [(j(!0), w(b, null, P(L(c)(e, n.value[e.code]), (n) => (j(), w("label", {
				key: n,
				class: "flex items-center gap-1.5"
			}, [T("span", _e, I(n), 1), D(R, {
				type: "number",
				step: "any",
				class: "w-32",
				disabled: t.disabled,
				"model-value": v(e.code)[n] ?? "",
				"data-testid": `showroom-value-${e.code}-${n}`,
				"onUpdate:modelValue": (t) => x(e.code, n, O(t))
			}, null, 8, [
				"disabled",
				"model-value",
				"data-testid",
				"onUpdate:modelValue"
			])]))), 128))])) : (j(), S(R, {
				key: 7,
				id: `showroom-value-${e.code}`,
				class: "w-full",
				disabled: t.disabled,
				"model-value": o(e.code),
				"data-testid": `showroom-value-${e.code}`,
				"onUpdate:modelValue": (t) => l(e.code, O(t))
			}, null, 8, [
				"id",
				"disabled",
				"model-value",
				"data-testid",
				"onUpdate:modelValue"
			])), t.error(`values.${e.code}`) ? (j(), w("small", {
				key: 8,
				class: "text-xs text-error",
				"data-testid": `showroom-value-error-${e.code}`
			}, I(t.error(`values.${e.code}`)), 9, ve)) : C("", !0)], 64)) : (j(), w("p", pe, I(L(r)("showroom.catalog.values.slot_unavailable")), 1))], 8, de))), 128))]));
		};
	}
});
export { Y as a, oe as i, le as n, ce as r, ye as t };
