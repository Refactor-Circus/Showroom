import { A as e, C as t, D as n, O as r, T as i, _ as a, a as o, b as s, f as c, g as l, h as u, k as d, m as f, o as ee, u as te, v as ne } from "./catalog-DCoO3A8a.js";
import { t as re } from "./CatalogActionError-BRUgojP9.js";
import { a as ie, i as ae, n as oe, r as se, t as ce } from "./ValueEditor-DaEpiyWK.js";
import { t as le } from "./CatalogState-Bkly-MlG.js";
import { a as ue, c as de, i as fe, l as p, n as pe, s as me, t as he, u as ge } from "./values-B4RtDnRM.js";
import { AtriumIntegrationScreen as _e, AtriumPageHeader as ve, navigateTo as ye } from "@refactor-circus/atrium";
import { UButton as be, UCard as xe, UFormField as Se, UInput as m, ULink as Ce, USelect as h } from "@refactor-circus/atrium/ui";
import { Fragment as g, computed as _, createBlock as v, createCommentVNode as y, createElementBlock as b, createElementVNode as x, createTextVNode as S, createVNode as C, defineComponent as w, isRef as T, mergeModels as E, openBlock as D, reactive as we, ref as Te, renderList as O, toDisplayString as k, unref as A, useModel as j, watch as M, withCtx as N, withModifiers as Ee } from "vue";
import { useI18n as P } from "vue-i18n";
import { useRoute as De } from "vue-router";
// src/components/catalog/AxesFields.vue?vue&type=script&setup=true&lang.ts
var F = ["data-axis"], I = { class: "text-sm font-medium" }, L = {
	key: 1,
	class: "grid grid-cols-2 gap-2"
}, R = {
	key: 3,
	class: "text-xs text-error"
}, Oe = /* @__PURE__ */ w({
	__name: "AxesFields",
	props: /*@__PURE__*/ E({
		axes: {},
		testid: {},
		error: { type: Function }
	}, {
		modelValue: { required: !0 },
		modelModifiers: {}
	}),
	emits: ["update:modelValue"],
	setup(e) {
		let t = e, n = j(e, "modelValue"), { t: r, locale: i } = P();
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
			return e == null ? "" : String(e);
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
		function d(e) {
			return e.type === "boolean" ? [{
				label: r("showroom.catalog.yes"),
				value: "1"
			}, {
				label: r("showroom.catalog.no"),
				value: "0"
			}] : (e.options ?? []).map((e) => ({
				label: c(e.labels, i.value, e.code),
				value: e.code
			}));
		}
		return (e, n) => {
			let s = h, f = m;
			return D(!0), b(g, null, O(t.axes, (e) => (D(), b("div", {
				key: e.code,
				class: "flex flex-col gap-1.5",
				"data-axis": e.code
			}, [
				x("label", I, k(A(c)(e.labels, A(i), e.code)), 1),
				e.type === "select" || e.type === "boolean" ? (D(), v(s, {
					key: 0,
					required: "",
					class: "w-48",
					items: d(e),
					placeholder: A(r)("showroom.catalog.none"),
					"model-value": a(e.code),
					"data-testid": `${t.testid}-axis-${e.code}`,
					"onUpdate:modelValue": (t) => l(e.code, t)
				}, null, 8, [
					"items",
					"placeholder",
					"model-value",
					"data-testid",
					"onUpdate:modelValue"
				])) : e.type === "metric" ? (D(), b("div", L, [C(f, {
					required: "",
					type: "number",
					step: "any",
					class: "w-48",
					"aria-label": A(r)("showroom.catalog.values.amount"),
					"model-value": o(e.code).amount,
					"data-testid": `${t.testid}-axis-${e.code}-amount`,
					"onUpdate:modelValue": (t) => u(e.code, "amount", t)
				}, null, 8, [
					"aria-label",
					"model-value",
					"data-testid",
					"onUpdate:modelValue"
				]), C(f, {
					required: "",
					class: "w-48",
					"aria-label": A(r)("showroom.catalog.values.unit"),
					"model-value": o(e.code).unit,
					"data-testid": `${t.testid}-axis-${e.code}-unit`,
					"onUpdate:modelValue": (t) => u(e.code, "unit", t)
				}, null, 8, [
					"aria-label",
					"model-value",
					"data-testid",
					"onUpdate:modelValue"
				])])) : (D(), v(f, {
					key: 2,
					required: "",
					class: "w-48",
					"model-value": a(e.code),
					"data-testid": `${t.testid}-axis-${e.code}`,
					"onUpdate:modelValue": (t) => l(e.code, t)
				}, null, 8, [
					"model-value",
					"data-testid",
					"onUpdate:modelValue"
				])),
				t.error(`values.${e.code}`) ? (D(), b("small", R, k(t.error(`values.${e.code}`)), 1)) : y("", !0)
			], 8, F))), 128);
		};
	}
}), ke = {
	class: "space-y-4",
	"data-testid": "showroom-product-model"
}, Ae = {
	key: 0,
	class: "space-y-5"
}, je = {
	class: "font-mono",
	"data-testid": "showroom-product-model-title"
}, Me = { class: "font-semibold" }, Ne = { class: "font-semibold" }, Pe = { class: "flex flex-col gap-2" }, Fe = {
	key: 0,
	class: "text-xs",
	"data-testid": "showroom-inherited-categories"
}, Ie = { key: 0 }, Le = { class: "font-semibold" }, Re = { class: "font-semibold" }, ze = { class: "space-y-4" }, Be = {
	key: 0,
	class: "flex flex-wrap gap-2"
}, Ve = {
	key: 1,
	class: "flex flex-wrap gap-2"
}, z = /* @__PURE__ */ w({
	__name: "[productModel]",
	setup(c) {
		let { t: h } = P(), w = De(), E = r(), j = r(), F = _(() => String(w.params.productModel)), { client: I, data: L, pending: R, failed: z, status: He, reload: B } = d(async (e) => {
			let n = t(await e.GET("/product-models/{productModel}", { params: { path: { productModel: F.value } } })).data;
			return {
				model: n,
				parent: n.parent ? t(await e.GET("/product-models/{productModel}", { params: { path: { productModel: n.parent } } })).data : null,
				variant: n.family_variant ? t(await e.GET("/family-variants/{familyVariant}", { params: { path: { familyVariant: n.family_variant } } })).data : null
			};
		}, F), V = _(() => L.value?.model ?? null), H = _(() => L.value?.parent ?? null), U = _(() => L.value?.variant ?? null), Ue = e(async (e) => t(await e.GET("/association-types", { params: { query: { per_page: 100 } } }))), We = _(() => U.value?.levels?.length ?? 1), W = _(() => (V.value?.level ?? 0) + 1 >= We.value), Ge = _(() => V.value ? fe(U.value, V.value.level) : []), Ke = _(() => V.value ? ue(U.value, W.value ? We.value : 1) : []), qe = me(() => [...Ge.value, ...Ke.value]), G = _(() => qe.pick(Ge.value)), K = _(() => qe.pick(Ke.value)), Je = _(() => H.value?.values ?? {}), Ye = _(() => a(V.value?.values, Je.value)), q = _(() => H.value?.categories ?? []), J = de(), Y = we({
			owner: "",
			categories: ""
		}), X = Te({}), Z = we({ key: "" }), Q = Te({});
		M([L, () => J.slot.value], () => {
			let e = V.value;
			e && (Y.owner = e.owner ?? "", Y.categories = u(e.categories, q.value).join(", "), X.value = p(G.value, Ye.value, J.slot.value));
		}, { immediate: !0 }), M(G, (e) => {
			let t = e.filter((e) => !(e.code in X.value));
			t.length > 0 && (X.value = {
				...p(t, Ye.value, J.slot.value),
				...X.value
			});
		}), M(K, (e) => {
			let t = e.filter((e) => !(e.code in Q.value));
			t.length > 0 && (Q.value = {
				...pe(t),
				...Q.value
			});
		}, { immediate: !0 });
		async function $(e) {
			return I ? t(await I.PATCH("/product-models/{productModel}", {
				params: { path: { productModel: F.value } },
				body: n(e)
			})) : null;
		}
		async function Xe() {
			let e = V.value;
			if (!e) return;
			let t = {
				...e.level === 0 ? { owner: o(Y.owner) } : {},
				categories: i(Y.categories),
				values: ge(G.value, X.value, J.slot.value)
			};
			await E.run(() => $(t)) && await B();
		}
		async function Ze() {
			I && window.confirm(h("showroom.catalog.product_models.confirm_delete")) && await E.run(async () => t(await I.DELETE("/product-models/{productModel}", { params: { path: { productModel: F.value } } }))) && await ye("/showroom/product-models");
		}
		async function Qe() {
			if (!I) return;
			let e = he(K.value, Q.value), r = Z.key.trim();
			await j.run(async () => W.value ? t(await I.POST("/products", { body: n({
				identifier: r,
				parent: F.value,
				values: e
			}) })) : t(await I.POST("/product-models", { body: n({
				code: r,
				parent: F.value,
				values: e
			}) }))) && (Z.key = "", Q.value = pe(K.value), await B());
		}
		return (e, t) => {
			let n = Ce, r = be, i = ve, a = m, o = Se, c = xe, u = _e;
			return D(), b("div", ke, [C(u, {
				"screen-key": "showroom.product-model",
				record: A(F)
			}, {
				default: N(() => [C(le, {
					ready: !A(R) && A(V) !== null,
					failed: A(z),
					status: A(He)
				}, {
					default: N(() => [A(V) ? (D(), b("div", Ae, [
						C(i, null, {
							title: N(() => [x("span", je, k(A(V).code), 1)]),
							description: N(() => [A(V).family_variant ? (D(), v(n, {
								key: 0,
								to: A(te)(A(V).family_variant),
								class: "font-mono hover:underline"
							}, {
								default: N(() => [S(k(A(V).family_variant), 1)]),
								_: 1
							}, 8, ["to"])) : y("", !0), S(" · " + k(A(h)("showroom.catalog.product_models.level", { level: A(V).level })), 1)]),
							default: N(() => [A(V).parent ? (D(), v(r, {
								key: 0,
								to: A(ne)(A(V).parent),
								variant: "outline",
								icon: "i-lucide-arrow-left",
								"data-testid": "showroom-product-model-parent"
							}, {
								default: N(() => [S(k(A(V).parent), 1)]),
								_: 1
							}, 8, ["to"])) : y("", !0), C(r, {
								color: "error",
								variant: "outline",
								icon: "i-lucide-trash-2",
								loading: A(E).busy.value,
								"data-testid": "showroom-product-model-delete",
								onClick: Ze
							}, {
								default: N(() => [S(k(A(h)("showroom.catalog.delete")), 1)]),
								_: 1
							}, 8, ["loading"])]),
							_: 1
						}),
						C(oe, {
							locales: A(J).locales.value,
							channels: A(J).channels.value,
							editing: A(J).slot.value,
							onPick: A(J).pick
						}, null, 8, [
							"locales",
							"channels",
							"editing",
							"onPick"
						]),
						x("form", {
							class: "space-y-5",
							"data-testid": "showroom-product-model-form",
							onSubmit: Ee(Xe, ["prevent"])
						}, [
							C(re, { action: A(E) }, null, 8, ["action"]),
							A(V).level === 0 ? (D(), v(c, { key: 0 }, {
								header: N(() => [x("h2", Me, k(A(h)("showroom.catalog.products.details")), 1)]),
								default: N(() => [C(o, {
									label: A(h)("showroom.catalog.fields.owner"),
									hint: A(h)("showroom.catalog.products.owner_hint"),
									error: A(E).error("owner")
								}, {
									default: N(() => [C(a, {
										modelValue: A(Y).owner,
										"onUpdate:modelValue": t[0] ||= (e) => A(Y).owner = e,
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
							})) : y("", !0),
							C(c, null, {
								header: N(() => [x("h2", Ne, k(A(h)("showroom.catalog.categories.title")), 1)]),
								default: N(() => [x("div", Pe, [C(o, {
									label: A(h)("showroom.catalog.categories.title"),
									hint: A(h)("showroom.catalog.categories.hint"),
									error: A(E).error("categories")
								}, {
									default: N(() => [C(a, {
										modelValue: A(Y).categories,
										"onUpdate:modelValue": t[1] ||= (e) => A(Y).categories = e,
										class: "w-full",
										"data-testid": "showroom-product-model-categories"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								]), A(q).length > 0 ? (D(), b("p", Fe, [S(k(A(h)("showroom.catalog.categories.inherited")) + ": ", 1), (D(!0), b(g, null, O(A(q), (e, t) => (D(), b(g, { key: e }, [C(n, {
									to: A(ee)(e),
									class: "font-mono hover:underline"
								}, {
									default: N(() => [S(k(e), 1)]),
									_: 2
								}, 1032, ["to"]), t < A(q).length - 1 ? (D(), b("span", Ie, ", ")) : y("", !0)], 64))), 128))])) : y("", !0)])]),
								_: 1
							}),
							C(c, null, {
								header: N(() => [x("h2", Le, k(A(h)("showroom.catalog.values.title")), 1)]),
								default: N(() => [C(ce, {
									modelValue: A(X),
									"onUpdate:modelValue": t[2] ||= (e) => T(X) ? X.value = e : null,
									attributes: A(G),
									editing: A(J).slot.value,
									error: A(E).error
								}, null, 8, [
									"modelValue",
									"attributes",
									"editing",
									"error"
								])]),
								_: 1
							}),
							x("div", null, [C(r, {
								type: "submit",
								icon: "i-lucide-check",
								loading: A(E).busy.value,
								"data-testid": "showroom-product-model-save"
							}, {
								default: N(() => [S(k(A(h)("showroom.catalog.save")), 1)]),
								_: 1
							}, 8, ["loading"])])
						], 32),
						C(ie, {
							assets: A(V).assets ?? [],
							"link-type": "product_model",
							"link-target": A(V).code,
							onChanged: A(B)
						}, null, 8, [
							"assets",
							"link-target",
							"onChanged"
						]),
						C(ae, {
							associations: A(V).associations ?? {},
							quantified: A(V).quantified_associations ?? {},
							"own-associations": A(f)(A(V).associations, A(H)?.associations),
							"own-quantified": A(l)(A(V).quantified_associations, A(H)?.quantified_associations),
							types: A(Ue),
							save: $,
							onChanged: A(B)
						}, null, 8, [
							"associations",
							"quantified",
							"own-associations",
							"own-quantified",
							"types",
							"onChanged"
						]),
						C(se, { values: A(Je) }, null, 8, ["values"]),
						C(c, { "data-testid": A(W) ? "showroom-variant-products" : "showroom-sub-models" }, {
							header: N(() => [x("h2", Re, k(A(W) ? A(h)("showroom.catalog.product_models.variant_products") : A(h)("showroom.catalog.product_models.sub_models")), 1)]),
							default: N(() => [x("div", ze, [
								A(W) && (A(V).products ?? []).length > 0 ? (D(), b("ul", Be, [(D(!0), b(g, null, O(A(V).products ?? [], (e) => (D(), b("li", { key: e }, [C(n, {
									to: A(s)(e),
									class: "font-mono text-sm hover:underline",
									"data-testid": `showroom-variant-product-${e}`
								}, {
									default: N(() => [S(k(e), 1)]),
									_: 2
								}, 1032, ["to", "data-testid"])]))), 128))])) : y("", !0),
								!A(W) && (A(V).children ?? []).length > 0 ? (D(), b("ul", Ve, [(D(!0), b(g, null, O(A(V).children ?? [], (e) => (D(), b("li", { key: e }, [C(n, {
									to: A(ne)(e),
									class: "font-mono text-sm hover:underline",
									"data-testid": `showroom-sub-model-${e}`
								}, {
									default: N(() => [S(k(e), 1)]),
									_: 2
								}, 1032, ["to", "data-testid"])]))), 128))])) : y("", !0),
								C(re, { action: A(j) }, null, 8, ["action"]),
								x("form", {
									class: "flex flex-wrap items-end gap-3",
									"data-testid": "showroom-child-create-form",
									onSubmit: Ee(Qe, ["prevent"])
								}, [
									C(o, {
										label: A(W) ? A(h)("showroom.catalog.fields.identifier") : A(h)("showroom.catalog.fields.code"),
										error: A(j).error(A(W) ? "identifier" : "code"),
										required: ""
									}, {
										default: N(() => [C(a, {
											modelValue: A(Z).key,
											"onUpdate:modelValue": t[3] ||= (e) => A(Z).key = e,
											required: "",
											class: "w-56",
											"data-testid": "showroom-child-key"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									C(Oe, {
										modelValue: A(Q),
										"onUpdate:modelValue": t[4] ||= (e) => T(Q) ? Q.value = e : null,
										axes: A(K),
										testid: "showroom-child",
										error: A(j).error
									}, null, 8, [
										"modelValue",
										"axes",
										"error"
									]),
									C(r, {
										type: "submit",
										icon: "i-lucide-plus",
										loading: A(j).busy.value,
										"data-testid": "showroom-child-create"
									}, {
										default: N(() => [S(k(A(W) ? A(h)("showroom.catalog.product_models.add_variant") : A(h)("showroom.catalog.product_models.add_sub_model")), 1)]),
										_: 1
									}, 8, ["loading"])
								], 32)
							])]),
							_: 1
						}, 8, ["data-testid"])
					])) : y("", !0)]),
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
export { z as default };
