import { A as e, C as t, D as n, O as r, T as i, _ as a, a as o, b as s, f as c, g as l, h as u, k as ee, m as te, o as ne, u as re, v as ie } from "./catalog-DCoO3A8a.js";
import { t as ae } from "./CatalogActionError-BRUgojP9.js";
import { a as oe, i as se, n as ce, r as le, t as ue } from "./ValueEditor-XSCs-XeG.js";
import { t as de } from "./CatalogState-Bkly-MlG.js";
import { a as fe, c as pe, i as me, l as he, n as ge, s as _e, t as ve, u as ye } from "./values-B4RtDnRM.js";
import { AtriumIntegrationScreen as be, navigateTo as xe } from "@refactor-circus/atrium";
import { UButton as Se, UCard as Ce, UFormField as we, UInput as Te } from "@refactor-circus/atrium/ui";
import { Fragment as d, computed as f, createBlock as p, createCommentVNode as m, createElementBlock as h, createElementVNode as g, createTextVNode as _, createVNode as v, defineComponent as y, isRef as Ee, mergeModels as De, normalizeClass as b, openBlock as x, reactive as S, ref as C, renderList as w, resolveComponent as Oe, toDisplayString as T, unref as E, useModel as D, watch as O, withCtx as k, withModifiers as A } from "vue";
import { useI18n as j } from "vue-i18n";
import { useRoute as ke } from "vue-router";
// src/components/catalog/AxesFields.vue?vue&type=script&setup=true&lang.ts
var M = ["data-axis"], N = { class: "text-sm font-medium" }, P = [
	"value",
	"data-testid",
	"onChange"
], F = {
	value: "",
	disabled: ""
}, Ae = ["value"], I = [
	"value",
	"data-testid",
	"onChange"
], L = {
	value: "",
	disabled: ""
}, R = { value: "1" }, z = { value: "0" }, B = {
	key: 2,
	class: "grid grid-cols-2 gap-2"
}, je = [
	"aria-label",
	"value",
	"data-testid",
	"onInput"
], V = [
	"aria-label",
	"value",
	"data-testid",
	"onInput"
], H = [
	"value",
	"data-testid",
	"onInput"
], U = {
	key: 4,
	class: "text-xs text-error"
}, W = "w-48 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm", Me = /* @__PURE__ */ y({
	__name: "AxesFields",
	props: /*@__PURE__*/ De({
		axes: {},
		testid: {},
		error: { type: Function }
	}, {
		modelValue: { required: !0 },
		modelModifiers: {}
	}),
	emits: ["update:modelValue"],
	setup(e) {
		let t = e, n = D(e, "modelValue"), { t: r, locale: i } = j();
		function a(e) {
			let t = n.value[e];
			return typeof t == "string" ? t : "";
		}
		function o(e) {
			let t = n.value[e];
			return t && typeof t == "object" && "amount" in t ? t : {
				amount: "",
				unit: ""
			};
		}
		function s(e) {
			return e.target.value;
		}
		function l(e, t) {
			n.value[e] = s(t);
		}
		function u(e, t, r) {
			n.value[e] = {
				...o(e),
				[t]: s(r)
			};
		}
		return (e, n) => (x(!0), h(d, null, w(t.axes, (e) => (x(), h("div", {
			key: e.code,
			class: "flex flex-col gap-1.5",
			"data-axis": e.code
		}, [
			g("label", N, T(E(c)(e.labels, E(i), e.code)), 1),
			e.type === "select" ? (x(), h("select", {
				key: 0,
				required: "",
				class: b(W),
				value: a(e.code),
				"data-testid": `${t.testid}-axis-${e.code}`,
				onChange: (t) => l(e.code, t)
			}, [g("option", F, T(E(r)("showroom.catalog.none")), 1), (x(!0), h(d, null, w(e.options ?? [], (e) => (x(), h("option", {
				key: e.code,
				value: e.code
			}, T(E(c)(e.labels, E(i), e.code)), 9, Ae))), 128))], 40, P)) : e.type === "boolean" ? (x(), h("select", {
				key: 1,
				required: "",
				class: b(W),
				value: a(e.code),
				"data-testid": `${t.testid}-axis-${e.code}`,
				onChange: (t) => l(e.code, t)
			}, [
				g("option", L, T(E(r)("showroom.catalog.none")), 1),
				g("option", R, T(E(r)("showroom.catalog.yes")), 1),
				g("option", z, T(E(r)("showroom.catalog.no")), 1)
			], 40, I)) : e.type === "metric" ? (x(), h("div", B, [g("input", {
				required: "",
				type: "number",
				step: "any",
				class: b(W),
				"aria-label": E(r)("showroom.catalog.values.amount"),
				value: o(e.code).amount,
				"data-testid": `${t.testid}-axis-${e.code}-amount`,
				onInput: (t) => u(e.code, "amount", t)
			}, null, 40, je), g("input", {
				required: "",
				class: b(W),
				"aria-label": E(r)("showroom.catalog.values.unit"),
				value: o(e.code).unit,
				"data-testid": `${t.testid}-axis-${e.code}-unit`,
				onInput: (t) => u(e.code, "unit", t)
			}, null, 40, V)])) : (x(), h("input", {
				key: 3,
				required: "",
				class: b(W),
				value: a(e.code),
				"data-testid": `${t.testid}-axis-${e.code}`,
				onInput: (t) => l(e.code, t)
			}, null, 40, H)),
			t.error(`values.${e.code}`) ? (x(), h("small", U, T(t.error(`values.${e.code}`)), 1)) : m("", !0)
		], 8, M))), 128));
	}
}), Ne = {
	class: "space-y-4",
	"data-testid": "showroom-product-model"
}, Pe = {
	key: 0,
	class: "space-y-5"
}, Fe = { class: "flex flex-wrap items-start justify-between gap-3" }, Ie = {
	class: "font-mono text-xl font-semibold",
	"data-testid": "showroom-product-model-title"
}, Le = { class: "text-sm text-muted" }, Re = { class: "flex flex-wrap items-center gap-2" }, ze = { class: "font-semibold" }, Be = { class: "font-semibold" }, Ve = { class: "flex flex-col gap-2" }, He = {
	key: 0,
	class: "text-xs",
	"data-testid": "showroom-inherited-categories"
}, Ue = { key: 0 }, We = { class: "font-semibold" }, Ge = { class: "font-semibold" }, Ke = { class: "space-y-4" }, qe = {
	key: 0,
	class: "flex flex-wrap gap-2"
}, Je = {
	key: 1,
	class: "flex flex-wrap gap-2"
}, G = /* @__PURE__ */ y({
	__name: "[productModel]",
	setup(c) {
		let { t: y } = j(), De = ke(), b = r(), D = r(), M = f(() => String(De.params.productModel)), { client: N, data: P, pending: F, failed: Ae, status: I, reload: L } = ee(async (e) => {
			let n = t(await e.GET("/product-models/{productModel}", { params: { path: { productModel: M.value } } })).data;
			return {
				model: n,
				parent: n.parent ? t(await e.GET("/product-models/{productModel}", { params: { path: { productModel: n.parent } } })).data : null,
				variant: n.family_variant ? t(await e.GET("/family-variants/{familyVariant}", { params: { path: { familyVariant: n.family_variant } } })).data : null
			};
		}, M), R = f(() => P.value?.model ?? null), z = f(() => P.value?.parent ?? null), B = f(() => P.value?.variant ?? null), je = e(async (e) => t(await e.GET("/association-types", { params: { query: { per_page: 100 } } }))), V = f(() => B.value?.levels?.length ?? 1), H = f(() => (R.value?.level ?? 0) + 1 >= V.value), U = f(() => R.value ? me(B.value, R.value.level) : []), W = f(() => R.value ? fe(B.value, H.value ? V.value : 1) : []), G = _e(() => [...U.value, ...W.value]), K = f(() => G.pick(U.value)), q = f(() => G.pick(W.value)), Ye = f(() => z.value?.values ?? {}), Xe = f(() => a(R.value?.values, Ye.value)), J = f(() => z.value?.categories ?? []), Y = pe(), X = S({
			owner: "",
			categories: ""
		}), Z = C({}), Q = S({ key: "" }), $ = C({});
		O([P, () => Y.slot.value], () => {
			let e = R.value;
			e && (X.owner = e.owner ?? "", X.categories = u(e.categories, J.value).join(", "), Z.value = he(K.value, Xe.value, Y.slot.value));
		}, { immediate: !0 }), O(K, (e) => {
			let t = e.filter((e) => !(e.code in Z.value));
			t.length > 0 && (Z.value = {
				...he(t, Xe.value, Y.slot.value),
				...Z.value
			});
		}), O(q, (e) => {
			let t = e.filter((e) => !(e.code in $.value));
			t.length > 0 && ($.value = {
				...ge(t),
				...$.value
			});
		}, { immediate: !0 });
		async function Ze(e) {
			return N ? t(await N.PATCH("/product-models/{productModel}", {
				params: { path: { productModel: M.value } },
				body: n(e)
			})) : null;
		}
		async function Qe() {
			let e = R.value;
			if (!e) return;
			let t = {
				...e.level === 0 ? { owner: o(X.owner) } : {},
				categories: i(X.categories),
				values: ye(K.value, Z.value, Y.slot.value)
			};
			await b.run(() => Ze(t)) && await L();
		}
		async function $e() {
			N && window.confirm(y("showroom.catalog.product_models.confirm_delete")) && await b.run(async () => t(await N.DELETE("/product-models/{productModel}", { params: { path: { productModel: M.value } } }))) && await xe("/showroom/product-models");
		}
		async function et() {
			if (!N) return;
			let e = ve(q.value, $.value), r = Q.key.trim();
			await D.run(async () => H.value ? t(await N.POST("/products", { body: n({
				identifier: r,
				parent: M.value,
				values: e
			}) })) : t(await N.POST("/product-models", { body: n({
				code: r,
				parent: M.value,
				values: e
			}) }))) && (Q.key = "", $.value = ge(q.value), await L());
		}
		return (e, t) => {
			let n = Oe("RouterLink"), r = Se, i = Te, a = we, o = Ce, c = be;
			return x(), h("div", Ne, [v(c, {
				"screen-key": "showroom.product-model",
				record: E(M)
			}, {
				default: k(() => [v(de, {
					ready: !E(F) && E(R) !== null,
					failed: E(Ae),
					status: E(I)
				}, {
					default: k(() => [E(R) ? (x(), h("div", Pe, [
						g("div", Fe, [g("div", null, [g("h1", Ie, T(E(R).code), 1), g("p", Le, [E(R).family_variant ? (x(), p(n, {
							key: 0,
							to: E(re)(E(R).family_variant),
							class: "font-mono hover:underline"
						}, {
							default: k(() => [_(T(E(R).family_variant), 1)]),
							_: 1
						}, 8, ["to"])) : m("", !0), _(" · " + T(E(y)("showroom.catalog.product_models.level", { level: E(R).level })), 1)])]), g("div", Re, [E(R).parent ? (x(), p(r, {
							key: 0,
							to: E(ie)(E(R).parent),
							variant: "outline",
							icon: "i-lucide-arrow-left",
							"data-testid": "showroom-product-model-parent"
						}, {
							default: k(() => [_(T(E(R).parent), 1)]),
							_: 1
						}, 8, ["to"])) : m("", !0), v(r, {
							color: "error",
							variant: "outline",
							icon: "i-lucide-trash-2",
							loading: E(b).busy.value,
							"data-testid": "showroom-product-model-delete",
							onClick: $e
						}, {
							default: k(() => [_(T(E(y)("showroom.catalog.delete")), 1)]),
							_: 1
						}, 8, ["loading"])])]),
						v(ce, {
							locales: E(Y).locales.value,
							channels: E(Y).channels.value,
							editing: E(Y).slot.value,
							onPick: E(Y).pick
						}, null, 8, [
							"locales",
							"channels",
							"editing",
							"onPick"
						]),
						g("form", {
							class: "space-y-5",
							"data-testid": "showroom-product-model-form",
							onSubmit: A(Qe, ["prevent"])
						}, [
							v(ae, { action: E(b) }, null, 8, ["action"]),
							E(R).level === 0 ? (x(), p(o, { key: 0 }, {
								header: k(() => [g("h2", ze, T(E(y)("showroom.catalog.products.details")), 1)]),
								default: k(() => [v(a, {
									label: E(y)("showroom.catalog.fields.owner"),
									hint: E(y)("showroom.catalog.products.owner_hint"),
									error: E(b).error("owner")
								}, {
									default: k(() => [v(i, {
										modelValue: E(X).owner,
										"onUpdate:modelValue": t[0] ||= (e) => E(X).owner = e,
										class: "w-56",
										"data-testid": "showroom-product-model-owner"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								])]),
								_: 1
							})) : m("", !0),
							v(o, null, {
								header: k(() => [g("h2", Be, T(E(y)("showroom.catalog.categories.title")), 1)]),
								default: k(() => [g("div", Ve, [v(a, {
									label: E(y)("showroom.catalog.categories.title"),
									hint: E(y)("showroom.catalog.categories.hint"),
									error: E(b).error("categories")
								}, {
									default: k(() => [v(i, {
										modelValue: E(X).categories,
										"onUpdate:modelValue": t[1] ||= (e) => E(X).categories = e,
										class: "w-full",
										"data-testid": "showroom-product-model-categories"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								]), E(J).length > 0 ? (x(), h("p", He, [_(T(E(y)("showroom.catalog.categories.inherited")) + ": ", 1), (x(!0), h(d, null, w(E(J), (e, t) => (x(), h(d, { key: e }, [v(n, {
									to: E(ne)(e),
									class: "font-mono hover:underline"
								}, {
									default: k(() => [_(T(e), 1)]),
									_: 2
								}, 1032, ["to"]), t < E(J).length - 1 ? (x(), h("span", Ue, ", ")) : m("", !0)], 64))), 128))])) : m("", !0)])]),
								_: 1
							}),
							v(o, null, {
								header: k(() => [g("h2", We, T(E(y)("showroom.catalog.values.title")), 1)]),
								default: k(() => [v(ue, {
									modelValue: E(Z),
									"onUpdate:modelValue": t[2] ||= (e) => Ee(Z) ? Z.value = e : null,
									attributes: E(K),
									editing: E(Y).slot.value,
									error: E(b).error
								}, null, 8, [
									"modelValue",
									"attributes",
									"editing",
									"error"
								])]),
								_: 1
							}),
							g("div", null, [v(r, {
								type: "submit",
								icon: "i-lucide-check",
								loading: E(b).busy.value,
								"data-testid": "showroom-product-model-save"
							}, {
								default: k(() => [_(T(E(y)("showroom.catalog.save")), 1)]),
								_: 1
							}, 8, ["loading"])])
						], 32),
						v(oe, {
							assets: E(R).assets ?? [],
							"link-type": "product_model",
							"link-target": E(R).code,
							onChanged: E(L)
						}, null, 8, [
							"assets",
							"link-target",
							"onChanged"
						]),
						v(se, {
							associations: E(R).associations ?? {},
							quantified: E(R).quantified_associations ?? {},
							"own-associations": E(te)(E(R).associations, E(z)?.associations),
							"own-quantified": E(l)(E(R).quantified_associations, E(z)?.quantified_associations),
							types: E(je),
							save: Ze,
							onChanged: E(L)
						}, null, 8, [
							"associations",
							"quantified",
							"own-associations",
							"own-quantified",
							"types",
							"onChanged"
						]),
						v(le, { values: E(Ye) }, null, 8, ["values"]),
						v(o, { "data-testid": E(H) ? "showroom-variant-products" : "showroom-sub-models" }, {
							header: k(() => [g("h2", Ge, T(E(H) ? E(y)("showroom.catalog.product_models.variant_products") : E(y)("showroom.catalog.product_models.sub_models")), 1)]),
							default: k(() => [g("div", Ke, [
								E(H) && (E(R).products ?? []).length > 0 ? (x(), h("ul", qe, [(x(!0), h(d, null, w(E(R).products ?? [], (e) => (x(), h("li", { key: e }, [v(n, {
									to: E(s)(e),
									class: "font-mono text-sm hover:underline",
									"data-testid": `showroom-variant-product-${e}`
								}, {
									default: k(() => [_(T(e), 1)]),
									_: 2
								}, 1032, ["to", "data-testid"])]))), 128))])) : m("", !0),
								!E(H) && (E(R).children ?? []).length > 0 ? (x(), h("ul", Je, [(x(!0), h(d, null, w(E(R).children ?? [], (e) => (x(), h("li", { key: e }, [v(n, {
									to: E(ie)(e),
									class: "font-mono text-sm hover:underline",
									"data-testid": `showroom-sub-model-${e}`
								}, {
									default: k(() => [_(T(e), 1)]),
									_: 2
								}, 1032, ["to", "data-testid"])]))), 128))])) : m("", !0),
								v(ae, { action: E(D) }, null, 8, ["action"]),
								g("form", {
									class: "flex flex-wrap items-end gap-3",
									"data-testid": "showroom-child-create-form",
									onSubmit: A(et, ["prevent"])
								}, [
									v(a, {
										label: E(H) ? E(y)("showroom.catalog.fields.identifier") : E(y)("showroom.catalog.fields.code"),
										error: E(D).error(E(H) ? "identifier" : "code"),
										required: ""
									}, {
										default: k(() => [v(i, {
											modelValue: E(Q).key,
											"onUpdate:modelValue": t[3] ||= (e) => E(Q).key = e,
											required: "",
											class: "w-56",
											"data-testid": "showroom-child-key"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									v(Me, {
										modelValue: E($),
										"onUpdate:modelValue": t[4] ||= (e) => Ee($) ? $.value = e : null,
										axes: E(q),
										testid: "showroom-child",
										error: E(D).error
									}, null, 8, [
										"modelValue",
										"axes",
										"error"
									]),
									v(r, {
										type: "submit",
										icon: "i-lucide-plus",
										loading: E(D).busy.value,
										"data-testid": "showroom-child-create"
									}, {
										default: k(() => [_(T(E(H) ? E(y)("showroom.catalog.product_models.add_variant") : E(y)("showroom.catalog.product_models.add_sub_model")), 1)]),
										_: 1
									}, 8, ["loading"])
								], 32)
							])]),
							_: 1
						}, 8, ["data-testid"])
					])) : m("", !0)]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])]),
				_: 1
			}, 8, ["record"])]);
		};
	}
});
export { G as default };
