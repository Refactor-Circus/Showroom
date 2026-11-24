import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { C as t, O as n, b as r, f as i, j as a, v as o } from "./catalog-DCoO3A8a.js";
import { o as s, r as c } from "./values-B4RtDnRM.js";
import { AtriumState as l } from "@refactor-circus/atrium";
import { UButton as u, UCard as d, UCheckbox as f, UFormField as p, UInput as m, ULink as h, USelect as g, UTextarea as _ } from "@refactor-circus/atrium/ui";
import { Fragment as v, computed as y, createBlock as b, createCommentVNode as x, createElementBlock as S, createElementVNode as C, createTextVNode as w, createVNode as T, defineComponent as E, isRef as D, mergeModels as O, openBlock as k, reactive as A, ref as j, renderList as M, shallowRef as N, toDisplayString as P, unref as F, useModel as I, useTemplateRef as L, watch as R, withCtx as z, withModifiers as B } from "vue";
import { useI18n as V } from "vue-i18n";
// src/components/catalog/CatalogAssets.vue?vue&type=script&setup=true&lang.ts
var H = { class: "font-semibold" }, U = { class: "space-y-4" }, ee = {
	key: 1,
	class: "grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6"
}, W = ["src", "alt"], G = {
	key: 1,
	class: "flex aspect-square items-center justify-center rounded bg-elevated font-mono text-xs text-muted"
}, K = { class: "truncate text-xs" }, q = /* @__PURE__ */ E({
	__name: "CatalogAssets",
	props: {
		assets: {},
		linkType: {},
		linkTarget: {}
	},
	emits: ["changed"],
	setup(r, { emit: i }) {
		let o = r, s = i, { t: c } = V(), f = a(), g = n(), _ = N(null), y = j("image"), x = L("fileInput");
		function E(e) {
			_.value = e.target.files?.[0] ?? null;
		}
		function O(e) {
			return !!e.url && (e.mime_type ?? "").startsWith("image/");
		}
		function A(e) {
			return [e.role, e.inherited ? c("showroom.catalog.inherited") : e.code].filter((e) => e).join(" · ");
		}
		async function I() {
			let e = _.value;
			f && e && await g.run(async () => {
				let n = new FormData();
				n.append("file", e);
				let r = t(await f.POST("/assets", {
					body: n,
					bodySerializer: (e) => e
				})).data;
				t(await f.POST("/assets/{asset}/links", {
					params: { path: { asset: r.code } },
					body: {
						type: o.linkType,
						target: o.linkTarget,
						...y.value.trim() ? { role: y.value.trim() } : {}
					}
				}));
			}) && (_.value = null, x.value?.inputRef && (x.value.inputRef.value = ""), s("changed"));
		}
		return (t, n) => {
			let r = l, i = h, a = m, s = p, f = u, _ = d;
			return k(), b(_, { "data-testid": "showroom-assets" }, {
				header: z(() => [C("h2", H, P(F(c)("showroom.catalog.assets.title")), 1)]),
				default: z(() => [C("div", U, [
					o.assets.length === 0 ? (k(), b(r, {
						key: 0,
						compact: "",
						title: F(c)("showroom.catalog.assets.none"),
						"data-testid": "showroom-assets-empty"
					}, null, 8, ["title"])) : (k(), S("div", ee, [(k(!0), S(v, null, M(o.assets, (e) => (k(), b(i, {
						key: `${e.inherited ? "inherited" : "own"}-${e.code}`,
						to: `/showroom/assets/${encodeURIComponent(e.code)}`,
						class: "flex flex-col gap-2 rounded-md border border-default p-2 hover:border-primary",
						"data-testid": `showroom-asset-${e.code}`
					}, {
						default: z(() => [O(e) ? (k(), S("img", {
							key: 0,
							src: e.url ?? void 0,
							alt: e.code,
							class: "aspect-square w-full rounded object-cover"
						}, null, 8, W)) : (k(), S("span", G, P(e.mime_type ?? e.code), 1)), C("span", K, P(A(e)), 1)]),
						_: 2
					}, 1032, ["to", "data-testid"]))), 128))])),
					T(e, { action: F(g) }, null, 8, ["action"]),
					C("form", {
						class: "flex flex-wrap items-end gap-3",
						"data-testid": "showroom-asset-upload",
						onSubmit: B(I, ["prevent"])
					}, [
						T(s, {
							label: F(c)("showroom.catalog.assets.upload_and_link"),
							error: F(g).error("file")
						}, {
							default: z(() => [T(a, {
								ref: "fileInput",
								type: "file",
								required: "",
								"data-testid": "showroom-asset-file",
								onChange: E
							}, null, 512)]),
							_: 1
						}, 8, ["label", "error"]),
						T(s, {
							label: F(c)("showroom.catalog.assets.role"),
							error: F(g).error("role")
						}, {
							default: z(() => [T(a, {
								modelValue: F(y),
								"onUpdate:modelValue": n[0] ||= (e) => D(y) ? y.value = e : null,
								class: "w-32",
								"data-testid": "showroom-asset-role"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						T(f, {
							type: "submit",
							icon: "i-lucide-upload",
							loading: F(g).busy.value,
							"data-testid": "showroom-asset-upload-submit"
						}, {
							default: z(() => [w(P(F(c)("showroom.catalog.assets.upload")), 1)]),
							_: 1
						}, 8, ["loading"])
					], 32)
				])]),
				_: 1
			});
		};
	}
}), J = { class: "font-semibold" }, Y = { class: "space-y-4" }, X = ["data-association-type"], Z = { class: "mb-1 font-mono text-xs font-medium uppercase text-muted" }, Q = { class: "flex flex-wrap gap-2" }, te = ["data-testid"], ne = {
	key: 0,
	class: "text-xs text-muted"
}, re = {
	key: 2,
	class: "text-xs text-muted"
}, ie = /* @__PURE__ */ E({
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
		let a = t, s = i, { t: c } = V(), f = n(), _ = ["products", "product_models"], E = A({
			type: "",
			kind: "products",
			target: "",
			quantity: ""
		}), D = y(() => a.types.map((e) => ({
			label: e.code,
			value: e.code
		}))), O = y(() => [{
			label: c("showroom.catalog.associations.product"),
			value: "products"
		}, {
			label: c("showroom.catalog.associations.product_model"),
			value: "product_models"
		}]), j = y(() => {
			let e = /* @__PURE__ */ new Map();
			for (let [t, n] of Object.entries(a.associations)) for (let r of _) for (let i of n[r]) {
				let n = a.ownAssociations[t]?.[r].includes(i) ?? !1;
				e.set(t, [...e.get(t) ?? [], {
					type: t,
					kind: r,
					identifier: i,
					quantity: null,
					own: n
				}]);
			}
			for (let [t, n] of Object.entries(a.quantified)) for (let r of _) for (let i of n[r]) {
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
		function N(e) {
			return a.types.find((t) => t.code === e)?.is_quantified ?? e in a.quantified;
		}
		function I(e) {
			return e.kind === "products" ? r(e.identifier) : o(e.identifier);
		}
		function L(e, t) {
			return N(e) ? { quantified_associations: { [e]: t } } : { associations: { [e]: t } };
		}
		async function R(e) {
			await f.run(() => a.save(e)) && (E.target = "", E.quantity = "", s("changed"));
		}
		async function H(e) {
			if (N(e.type)) {
				let t = a.ownQuantified[e.type] ?? {
					products: [],
					product_models: []
				};
				await R(L(e.type, {
					...t,
					[e.kind]: t[e.kind].filter((t) => t.identifier !== e.identifier)
				}));
			} else {
				let t = a.ownAssociations[e.type] ?? {
					products: [],
					product_models: []
				};
				await R(L(e.type, {
					...t,
					[e.kind]: t[e.kind].filter((t) => t !== e.identifier)
				}));
			}
		}
		async function U() {
			let e = E.target.trim();
			if (E.type && e !== "") {
				if (N(E.type)) {
					let t = a.ownQuantified[E.type] ?? {
						products: [],
						product_models: []
					}, n = Number(E.quantity) >= 1 ? Math.trunc(Number(E.quantity)) : 1;
					await R(L(E.type, {
						...t,
						[E.kind]: [...t[E.kind].filter((t) => t.identifier !== e), {
							identifier: e,
							quantity: n
						}]
					}));
				} else {
					let t = a.ownAssociations[E.type] ?? {
						products: [],
						product_models: []
					};
					await R(L(E.type, {
						...t,
						[E.kind]: [...t[E.kind].filter((t) => t !== e), e]
					}));
				}
			}
		}
		return (t, n) => {
			let r = l, i = h, o = u, s = g, _ = p, y = m, A = d;
			return k(), b(A, { "data-testid": "showroom-associations" }, {
				header: z(() => [C("h2", J, P(F(c)("showroom.catalog.associations.title")), 1)]),
				default: z(() => [C("div", Y, [
					F(j).length === 0 ? (k(), b(r, {
						key: 0,
						compact: "",
						title: F(c)("showroom.catalog.associations.none"),
						"data-testid": "showroom-associations-empty"
					}, null, 8, ["title"])) : x("", !0),
					(k(!0), S(v, null, M(F(j), ([e, t]) => (k(), S("div", {
						key: e,
						"data-association-type": e
					}, [C("p", Z, P(e), 1), C("ul", Q, [(k(!0), S(v, null, M(t, (t) => (k(), S("li", {
						key: `${t.kind}-${t.identifier}`,
						class: "flex items-center gap-1.5 rounded-md border border-default px-2 py-1 text-sm",
						"data-testid": `showroom-association-${e}-${t.identifier}`
					}, [
						T(i, {
							to: I(t),
							class: "font-mono hover:underline"
						}, {
							default: z(() => [w(P(t.identifier), 1)]),
							_: 2
						}, 1032, ["to"]),
						t.quantity === null ? x("", !0) : (k(), S("span", ne, "× " + P(t.quantity), 1)),
						t.own ? (k(), b(o, {
							key: 1,
							size: "xs",
							variant: "ghost",
							icon: "i-lucide-x",
							"aria-label": F(c)("showroom.catalog.associations.remove"),
							loading: F(f).busy.value,
							"data-testid": `showroom-association-remove-${e}-${t.identifier}`,
							onClick: (e) => H(t)
						}, null, 8, [
							"aria-label",
							"loading",
							"data-testid",
							"onClick"
						])) : (k(), S("span", re, P(F(c)("showroom.catalog.inherited")), 1))
					], 8, te))), 128))])], 8, X))), 128)),
					T(e, { action: F(f) }, null, 8, ["action"]),
					a.types.length > 0 ? (k(), S("form", {
						key: 1,
						class: "flex flex-wrap items-end gap-3",
						"data-testid": "showroom-association-add",
						onSubmit: B(U, ["prevent"])
					}, [
						T(_, {
							label: F(c)("showroom.catalog.associations.type"),
							error: F(f).error("associations") ?? F(f).error("quantified_associations")
						}, {
							default: z(() => [T(s, {
								modelValue: F(E).type,
								"onUpdate:modelValue": n[0] ||= (e) => F(E).type = e,
								items: F(D),
								placeholder: F(c)("showroom.catalog.associations.choose_type"),
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
						T(_, { label: F(c)("showroom.catalog.associations.target_kind") }, {
							default: z(() => [T(s, {
								modelValue: F(E).kind,
								"onUpdate:modelValue": n[1] ||= (e) => F(E).kind = e,
								items: F(O),
								class: "w-40",
								"data-testid": "showroom-association-kind"
							}, null, 8, ["modelValue", "items"])]),
							_: 1
						}, 8, ["label"]),
						T(_, { label: F(c)("showroom.catalog.associations.target") }, {
							default: z(() => [T(y, {
								modelValue: F(E).target,
								"onUpdate:modelValue": n[2] ||= (e) => F(E).target = e,
								required: "",
								class: "w-44",
								"data-testid": "showroom-association-target"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						T(_, {
							label: F(c)("showroom.catalog.associations.quantity"),
							hint: F(c)("showroom.catalog.associations.quantity_hint")
						}, {
							default: z(() => [T(y, {
								modelValue: F(E).quantity,
								"onUpdate:modelValue": n[3] ||= (e) => F(E).quantity = e,
								type: "number",
								min: "1",
								class: "w-28",
								"data-testid": "showroom-association-quantity"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "hint"]),
						T(o, {
							type: "submit",
							icon: "i-lucide-plus",
							loading: F(f).busy.value,
							"data-testid": "showroom-association-add-submit"
						}, {
							default: z(() => [w(P(F(c)("showroom.catalog.associations.add")), 1)]),
							_: 1
						}, 8, ["loading"])
					], 32)) : x("", !0)
				])]),
				_: 1
			});
		};
	}
}), ae = { class: "font-semibold" }, oe = { class: "grid gap-x-4 gap-y-2 text-sm sm:grid-cols-[auto_1fr]" }, se = { class: "font-mono text-xs text-muted" }, ce = ["data-testid"], le = /* @__PURE__ */ E({
	__name: "InheritedValues",
	props: { values: {} },
	setup(e) {
		let t = e, { t: n } = V(), r = y(() => Object.entries(t.values).flatMap(([e, t]) => t.map((t) => ({
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
			let a = d;
			return F(r).length > 0 ? (k(), b(a, {
				key: 0,
				"data-testid": "showroom-inherited-values"
			}, {
				header: z(() => [C("h2", ae, P(F(n)("showroom.catalog.values.inherited")), 1)]),
				default: z(() => [C("dl", oe, [(k(!0), S(v, null, M(F(r), (e) => (k(), S(v, { key: e.key }, [C("dt", se, P(e.term), 1), C("dd", { "data-testid": `showroom-inherited-${e.key}` }, P(i(e.data)), 9, ce)], 64))), 128))])]),
				_: 1
			})) : x("", !0);
		};
	}
}), ue = /* @__PURE__ */ E({
	__name: "SlotPicker",
	props: {
		locales: {},
		channels: {},
		editing: {}
	},
	emits: ["pick"],
	setup(e, { emit: t }) {
		let n = e, r = t, { t: a, locale: o } = V(), s = A({
			locale: "",
			channel: ""
		});
		R(() => n.editing, (e) => {
			s.locale = e.locale ?? "", s.channel = e.channel ?? "";
		}, { immediate: !0 });
		let c = y(() => n.locales.map((e) => ({
			label: i(e.labels, o.value, e.code),
			value: e.code
		}))), l = y(() => n.channels.map((e) => ({
			label: i(e.labels, o.value, e.code),
			value: e.code
		})));
		function f() {
			r("pick", {
				locale: s.locale || null,
				channel: s.channel || null
			});
		}
		return (e, t) => {
			let r = g, i = p, o = u, m = d;
			return n.locales.length > 0 || n.channels.length > 0 ? (k(), b(m, { key: 0 }, {
				default: z(() => [C("form", {
					class: "flex flex-wrap items-end gap-3",
					"data-testid": "showroom-slot-picker",
					onSubmit: B(f, ["prevent"])
				}, [
					n.locales.length > 0 ? (k(), b(i, {
						key: 0,
						label: F(a)("showroom.catalog.fields.locale")
					}, {
						default: z(() => [T(r, {
							modelValue: F(s).locale,
							"onUpdate:modelValue": t[0] ||= (e) => F(s).locale = e,
							items: F(c),
							class: "w-44",
							"data-testid": "showroom-slot-locale"
						}, null, 8, ["modelValue", "items"])]),
						_: 1
					}, 8, ["label"])) : x("", !0),
					n.channels.length > 0 ? (k(), b(i, {
						key: 1,
						label: F(a)("showroom.catalog.fields.channel")
					}, {
						default: z(() => [T(r, {
							modelValue: F(s).channel,
							"onUpdate:modelValue": t[1] ||= (e) => F(s).channel = e,
							items: F(l),
							class: "w-44",
							"data-testid": "showroom-slot-channel"
						}, null, 8, ["modelValue", "items"])]),
						_: 1
					}, 8, ["label"])) : x("", !0),
					T(o, {
						type: "submit",
						variant: "outline",
						icon: "i-lucide-arrow-left-right",
						"data-testid": "showroom-slot-switch"
					}, {
						default: z(() => [w(P(F(a)("showroom.catalog.values.switch")), 1)]),
						_: 1
					})
				], 32)]),
				_: 1
			})) : x("", !0);
		};
	}
}), de = {
	key: 1,
	class: "grid gap-4 sm:grid-cols-2",
	"data-testid": "showroom-values"
}, fe = ["data-attribute"], pe = ["for"], me = { class: "font-mono text-xs font-normal text-muted" }, he = {
	key: 0,
	class: "text-xs text-muted"
}, ge = {
	key: 4,
	class: "flex flex-wrap gap-3"
}, $ = {
	key: 5,
	class: "grid grid-cols-2 gap-2"
}, _e = {
	key: 6,
	class: "flex flex-wrap gap-2"
}, ve = { class: "font-mono text-xs" }, ye = ["data-testid"], be = /* @__PURE__ */ E({
	__name: "ValueEditor",
	props: /*@__PURE__*/ O({
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
		let t = e, n = I(e, "modelValue"), { t: r, locale: a } = V();
		function o(e) {
			let t = n.value[e];
			return typeof t == "string" ? t : "";
		}
		function u(e, t) {
			n.value[e] = t;
		}
		function d(e) {
			let t = n.value[e];
			return Array.isArray(t) ? t : [];
		}
		function p(e, t, r) {
			let i = d(e).filter((e) => e !== t);
			n.value[e] = r ? [...i, t] : i;
		}
		function h(e) {
			let t = n.value[e];
			return t && typeof t == "object" && !Array.isArray(t) && "amount" in t ? t : {
				amount: "",
				unit: ""
			};
		}
		function y(e, t, r) {
			n.value[e] = {
				...h(e),
				[t]: r
			};
		}
		function E(e) {
			let t = n.value[e];
			return t && typeof t == "object" && !Array.isArray(t) && !("amount" in t) ? t : {};
		}
		function D(e, t, r) {
			n.value[e] = {
				...E(e),
				[t]: r
			};
		}
		function O(e) {
			return e == null ? "" : String(e);
		}
		function A(e) {
			let t = e.type === "boolean" ? [{
				label: r("showroom.catalog.yes"),
				value: "1"
			}, {
				label: r("showroom.catalog.no"),
				value: "0"
			}] : (e.options ?? []).map((e) => ({
				label: i(e.labels, a.value, e.code),
				value: e.code
			}));
			return [{
				label: r("showroom.catalog.none"),
				value: null
			}, ...t];
		}
		function j(e) {
			return [
				e.code,
				e.type,
				e.is_localizable ? t.editing.locale : null,
				e.is_scopable ? t.editing.channel : null
			].filter((e) => e).join(" · ");
		}
		return (e, N) => {
			let I = l, L = _, R = m, z = g, B = f;
			return t.attributes.length === 0 ? (k(), b(I, {
				key: 0,
				compact: "",
				title: F(r)("showroom.catalog.values.none_settable"),
				"data-testid": "showroom-values-empty"
			}, null, 8, ["title"])) : (k(), S("div", de, [(k(!0), S(v, null, M(t.attributes, (e) => (k(), S("div", {
				key: e.code,
				class: "flex flex-col gap-1.5",
				"data-attribute": e.code
			}, [C("label", {
				class: "text-sm font-medium",
				for: `showroom-value-${e.code}`
			}, [w(P(F(i)(e.labels, F(a), e.code)) + " ", 1), C("span", me, P(j(e)), 1)], 8, pe), F(s)(e, t.editing) ? (k(), S(v, { key: 1 }, [e.type === "textarea" ? (k(), b(L, {
				key: 0,
				id: `showroom-value-${e.code}`,
				class: "w-full",
				rows: 3,
				disabled: t.disabled,
				"model-value": o(e.code),
				"data-testid": `showroom-value-${e.code}`,
				"onUpdate:modelValue": (t) => u(e.code, O(t))
			}, null, 8, [
				"id",
				"disabled",
				"model-value",
				"data-testid",
				"onUpdate:modelValue"
			])) : e.type === "number" || e.type === "decimal" ? (k(), b(R, {
				key: 1,
				id: `showroom-value-${e.code}`,
				type: "number",
				step: e.type === "number" ? "1" : "any",
				class: "w-full",
				disabled: t.disabled,
				"model-value": o(e.code),
				"data-testid": `showroom-value-${e.code}`,
				"onUpdate:modelValue": (t) => u(e.code, O(t))
			}, null, 8, [
				"id",
				"step",
				"disabled",
				"model-value",
				"data-testid",
				"onUpdate:modelValue"
			])) : e.type === "date" ? (k(), b(R, {
				key: 2,
				id: `showroom-value-${e.code}`,
				type: "date",
				class: "w-full",
				disabled: t.disabled,
				"model-value": o(e.code),
				"data-testid": `showroom-value-${e.code}`,
				"onUpdate:modelValue": (t) => u(e.code, O(t))
			}, null, 8, [
				"id",
				"disabled",
				"model-value",
				"data-testid",
				"onUpdate:modelValue"
			])) : e.type === "boolean" || e.type === "select" ? (k(), b(z, {
				key: 3,
				id: `showroom-value-${e.code}`,
				class: "w-full",
				items: A(e),
				placeholder: F(r)("showroom.catalog.none"),
				disabled: t.disabled,
				"model-value": o(e.code),
				"data-testid": `showroom-value-${e.code}`,
				"onUpdate:modelValue": (t) => u(e.code, O(t))
			}, null, 8, [
				"id",
				"items",
				"placeholder",
				"disabled",
				"model-value",
				"data-testid",
				"onUpdate:modelValue"
			])) : e.type === "multiselect" ? (k(), S("div", ge, [(k(!0), S(v, null, M(e.options ?? [], (n) => (k(), b(B, {
				key: n.code,
				label: F(i)(n.labels, F(a), n.code),
				disabled: t.disabled,
				"model-value": d(e.code).includes(n.code),
				"data-testid": `showroom-value-${e.code}-${n.code}`,
				"onUpdate:modelValue": (t) => p(e.code, n.code, t === !0)
			}, null, 8, [
				"label",
				"disabled",
				"model-value",
				"data-testid",
				"onUpdate:modelValue"
			]))), 128))])) : e.type === "metric" ? (k(), S("div", $, [T(R, {
				id: `showroom-value-${e.code}`,
				type: "number",
				step: "any",
				"aria-label": F(r)("showroom.catalog.values.amount"),
				disabled: t.disabled,
				"model-value": h(e.code).amount,
				"data-testid": `showroom-value-${e.code}-amount`,
				"onUpdate:modelValue": (t) => y(e.code, "amount", O(t))
			}, null, 8, [
				"id",
				"aria-label",
				"disabled",
				"model-value",
				"data-testid",
				"onUpdate:modelValue"
			]), T(R, {
				"aria-label": F(r)("showroom.catalog.values.unit"),
				disabled: t.disabled,
				"model-value": h(e.code).unit,
				"data-testid": `showroom-value-${e.code}-unit`,
				"onUpdate:modelValue": (t) => y(e.code, "unit", O(t))
			}, null, 8, [
				"aria-label",
				"disabled",
				"model-value",
				"data-testid",
				"onUpdate:modelValue"
			])])) : e.type === "price" ? (k(), S("div", _e, [(k(!0), S(v, null, M(F(c)(e, n.value[e.code]), (n) => (k(), S("label", {
				key: n,
				class: "flex items-center gap-1.5"
			}, [C("span", ve, P(n), 1), T(R, {
				type: "number",
				step: "any",
				class: "w-32",
				disabled: t.disabled,
				"model-value": E(e.code)[n] ?? "",
				"data-testid": `showroom-value-${e.code}-${n}`,
				"onUpdate:modelValue": (t) => D(e.code, n, O(t))
			}, null, 8, [
				"disabled",
				"model-value",
				"data-testid",
				"onUpdate:modelValue"
			])]))), 128))])) : (k(), b(R, {
				key: 7,
				id: `showroom-value-${e.code}`,
				class: "w-full",
				disabled: t.disabled,
				"model-value": o(e.code),
				"data-testid": `showroom-value-${e.code}`,
				"onUpdate:modelValue": (t) => u(e.code, O(t))
			}, null, 8, [
				"id",
				"disabled",
				"model-value",
				"data-testid",
				"onUpdate:modelValue"
			])), t.error(`values.${e.code}`) ? (k(), S("small", {
				key: 8,
				class: "text-xs text-error",
				"data-testid": `showroom-value-error-${e.code}`
			}, P(t.error(`values.${e.code}`)), 9, ye)) : x("", !0)], 64)) : (k(), S("p", he, P(F(r)("showroom.catalog.values.slot_unavailable")), 1))], 8, fe))), 128))]));
		};
	}
});
export { q as a, ie as i, ue as n, le as r, be as t };
