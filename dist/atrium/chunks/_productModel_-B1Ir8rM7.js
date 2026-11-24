import { A as e, C as t, D as n, O as r, T as i, _ as a, a as o, b as s, f as c, g as l, h as u, k as d, m as f, o as ee, u as te, v as ne } from "./catalog-DCoO3A8a.js";
import { t as re } from "./CatalogActionError-BRUgojP9.js";
import { a as ie, i as ae, n as oe, r as se, t as ce } from "./ValueEditor-DLdUXpYp.js";
import { t as le } from "./CatalogState-Bkly-MlG.js";
import { a as ue, c as de, i as fe, l as pe, n as me, s as he, t as ge, u as _e } from "./values-B4RtDnRM.js";
import { AtriumIntegrationScreen as ve, AtriumPageHeader as ye, navigateTo as be } from "@refactor-circus/atrium";
import { UButton as xe, UCard as Se, UFormField as p, UInput as m, ULink as Ce, USelect as h } from "@refactor-circus/atrium/ui";
import { Fragment as g, computed as _, createBlock as v, createCommentVNode as y, createElementBlock as b, createElementVNode as x, createTextVNode as S, createVNode as C, defineComponent as w, isRef as we, mergeModels as T, openBlock as E, reactive as D, ref as O, renderList as k, toDisplayString as A, unref as j, useModel as M, watch as N, withCtx as P, withModifiers as F } from "vue";
import { useI18n as I } from "vue-i18n";
import { useRoute as Te } from "vue-router";
// src/components/catalog/AxesFields.vue?vue&type=script&setup=true&lang.ts
var L = {
	key: 1,
	class: "grid grid-cols-2 gap-2"
}, Ee = /* @__PURE__ */ w({
	__name: "AxesFields",
	props: /*@__PURE__*/ T({
		axes: {},
		testid: {},
		error: { type: Function }
	}, {
		modelValue: { required: !0 },
		modelModifiers: {}
	}),
	emits: ["update:modelValue"],
	setup(e) {
		let t = e, n = M(e, "modelValue"), { t: r, locale: i } = I();
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
			let s = h, f = m, ee = p;
			return E(!0), b(g, null, k(t.axes, (e) => (E(), v(ee, {
				key: e.code,
				label: j(c)(e.labels, j(i), e.code),
				error: t.error(`values.${e.code}`),
				"data-axis": e.code
			}, {
				default: P(() => [e.type === "select" || e.type === "boolean" ? (E(), v(s, {
					key: 0,
					required: "",
					class: "w-48",
					items: d(e),
					placeholder: j(r)("showroom.catalog.none"),
					"model-value": a(e.code),
					"data-testid": `${t.testid}-axis-${e.code}`,
					"onUpdate:modelValue": (t) => l(e.code, t)
				}, null, 8, [
					"items",
					"placeholder",
					"model-value",
					"data-testid",
					"onUpdate:modelValue"
				])) : e.type === "metric" ? (E(), b("div", L, [C(f, {
					required: "",
					type: "number",
					step: "any",
					class: "w-48",
					"aria-label": j(r)("showroom.catalog.values.amount"),
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
					"aria-label": j(r)("showroom.catalog.values.unit"),
					"model-value": o(e.code).unit,
					"data-testid": `${t.testid}-axis-${e.code}-unit`,
					"onUpdate:modelValue": (t) => u(e.code, "unit", t)
				}, null, 8, [
					"aria-label",
					"model-value",
					"data-testid",
					"onUpdate:modelValue"
				])])) : (E(), v(f, {
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
				]))]),
				_: 2
			}, 1032, [
				"label",
				"error",
				"data-axis"
			]))), 128);
		};
	}
}), De = {
	class: "space-y-4",
	"data-testid": "showroom-product-model"
}, Oe = {
	key: 0,
	class: "space-y-5"
}, ke = {
	class: "font-mono",
	"data-testid": "showroom-product-model-title"
}, Ae = { class: "font-semibold" }, je = { class: "font-semibold" }, Me = { class: "flex flex-col gap-2" }, Ne = {
	key: 0,
	class: "text-xs",
	"data-testid": "showroom-inherited-categories"
}, Pe = { key: 0 }, Fe = { class: "font-semibold" }, Ie = { class: "font-semibold" }, Le = { class: "space-y-4" }, Re = {
	key: 0,
	class: "flex flex-wrap gap-2"
}, ze = {
	key: 1,
	class: "flex flex-wrap gap-2"
}, R = /* @__PURE__ */ w({
	__name: "[productModel]",
	setup(c) {
		let { t: h } = I(), w = Te(), T = r(), M = r(), L = _(() => String(w.params.productModel)), { client: R, data: z, pending: Be, failed: Ve, status: He, reload: B } = d(async (e) => {
			let n = t(await e.GET("/product-models/{productModel}", { params: { path: { productModel: L.value } } })).data;
			return {
				model: n,
				parent: n.parent ? t(await e.GET("/product-models/{productModel}", { params: { path: { productModel: n.parent } } })).data : null,
				variant: n.family_variant ? t(await e.GET("/family-variants/{familyVariant}", { params: { path: { familyVariant: n.family_variant } } })).data : null
			};
		}, L), V = _(() => z.value?.model ?? null), H = _(() => z.value?.parent ?? null), U = _(() => z.value?.variant ?? null), Ue = e(async (e) => t(await e.GET("/association-types", { params: { query: { per_page: 100 } } }))), We = _(() => U.value?.levels?.length ?? 1), W = _(() => (V.value?.level ?? 0) + 1 >= We.value), Ge = _(() => V.value ? fe(U.value, V.value.level) : []), Ke = _(() => V.value ? ue(U.value, W.value ? We.value : 1) : []), qe = he(() => [...Ge.value, ...Ke.value]), G = _(() => qe.pick(Ge.value)), K = _(() => qe.pick(Ke.value)), Je = _(() => H.value?.values ?? {}), Ye = _(() => a(V.value?.values, Je.value)), q = _(() => H.value?.categories ?? []), J = de(), Y = D({
			owner: "",
			categories: ""
		}), X = O({}), Z = D({ key: "" }), Q = O({});
		N([z, () => J.slot.value], () => {
			let e = V.value;
			e && (Y.owner = e.owner ?? "", Y.categories = u(e.categories, q.value).join(", "), X.value = pe(G.value, Ye.value, J.slot.value));
		}, { immediate: !0 }), N(G, (e) => {
			let t = e.filter((e) => !(e.code in X.value));
			t.length > 0 && (X.value = {
				...pe(t, Ye.value, J.slot.value),
				...X.value
			});
		}), N(K, (e) => {
			let t = e.filter((e) => !(e.code in Q.value));
			t.length > 0 && (Q.value = {
				...me(t),
				...Q.value
			});
		}, { immediate: !0 });
		async function $(e) {
			return R ? t(await R.PATCH("/product-models/{productModel}", {
				params: { path: { productModel: L.value } },
				body: n(e)
			})) : null;
		}
		async function Xe() {
			let e = V.value;
			if (!e) return;
			let t = {
				...e.level === 0 ? { owner: o(Y.owner) } : {},
				categories: i(Y.categories),
				values: _e(G.value, X.value, J.slot.value)
			};
			await T.run(() => $(t)) && await B();
		}
		async function Ze() {
			R && window.confirm(h("showroom.catalog.product_models.confirm_delete")) && await T.run(async () => t(await R.DELETE("/product-models/{productModel}", { params: { path: { productModel: L.value } } }))) && await be("/showroom/product-models");
		}
		async function Qe() {
			if (!R) return;
			let e = ge(K.value, Q.value), r = Z.key.trim();
			await M.run(async () => W.value ? t(await R.POST("/products", { body: n({
				identifier: r,
				parent: L.value,
				values: e
			}) })) : t(await R.POST("/product-models", { body: n({
				code: r,
				parent: L.value,
				values: e
			}) }))) && (Z.key = "", Q.value = me(K.value), await B());
		}
		return (e, t) => {
			let n = Ce, r = xe, i = ye, a = m, o = p, c = Se, u = ve;
			return E(), b("div", De, [C(u, {
				"screen-key": "showroom.product-model",
				record: j(L)
			}, {
				default: P(() => [C(le, {
					ready: !j(Be) && j(V) !== null,
					failed: j(Ve),
					status: j(He)
				}, {
					default: P(() => [j(V) ? (E(), b("div", Oe, [
						C(i, null, {
							title: P(() => [x("span", ke, A(j(V).code), 1)]),
							description: P(() => [j(V).family_variant ? (E(), v(n, {
								key: 0,
								to: j(te)(j(V).family_variant),
								class: "font-mono hover:underline"
							}, {
								default: P(() => [S(A(j(V).family_variant), 1)]),
								_: 1
							}, 8, ["to"])) : y("", !0), S(" · " + A(j(h)("showroom.catalog.product_models.level", { level: j(V).level })), 1)]),
							default: P(() => [j(V).parent ? (E(), v(r, {
								key: 0,
								to: j(ne)(j(V).parent),
								variant: "outline",
								icon: "i-lucide-arrow-left",
								"data-testid": "showroom-product-model-parent"
							}, {
								default: P(() => [S(A(j(V).parent), 1)]),
								_: 1
							}, 8, ["to"])) : y("", !0), C(r, {
								color: "error",
								variant: "outline",
								icon: "i-lucide-trash-2",
								loading: j(T).busy.value,
								"data-testid": "showroom-product-model-delete",
								onClick: Ze
							}, {
								default: P(() => [S(A(j(h)("showroom.catalog.delete")), 1)]),
								_: 1
							}, 8, ["loading"])]),
							_: 1
						}),
						C(oe, {
							locales: j(J).locales.value,
							channels: j(J).channels.value,
							editing: j(J).slot.value,
							onPick: j(J).pick
						}, null, 8, [
							"locales",
							"channels",
							"editing",
							"onPick"
						]),
						x("form", {
							class: "space-y-5",
							"data-testid": "showroom-product-model-form",
							onSubmit: F(Xe, ["prevent"])
						}, [
							C(re, { action: j(T) }, null, 8, ["action"]),
							j(V).level === 0 ? (E(), v(c, { key: 0 }, {
								header: P(() => [x("h2", Ae, A(j(h)("showroom.catalog.products.details")), 1)]),
								default: P(() => [C(o, {
									label: j(h)("showroom.catalog.fields.owner"),
									hint: j(h)("showroom.catalog.products.owner_hint"),
									error: j(T).error("owner")
								}, {
									default: P(() => [C(a, {
										modelValue: j(Y).owner,
										"onUpdate:modelValue": t[0] ||= (e) => j(Y).owner = e,
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
								header: P(() => [x("h2", je, A(j(h)("showroom.catalog.categories.title")), 1)]),
								default: P(() => [x("div", Me, [C(o, {
									label: j(h)("showroom.catalog.categories.title"),
									hint: j(h)("showroom.catalog.categories.hint"),
									error: j(T).error("categories")
								}, {
									default: P(() => [C(a, {
										modelValue: j(Y).categories,
										"onUpdate:modelValue": t[1] ||= (e) => j(Y).categories = e,
										class: "w-full",
										"data-testid": "showroom-product-model-categories"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								]), j(q).length > 0 ? (E(), b("p", Ne, [S(A(j(h)("showroom.catalog.categories.inherited")) + ": ", 1), (E(!0), b(g, null, k(j(q), (e, t) => (E(), b(g, { key: e }, [C(n, {
									to: j(ee)(e),
									class: "font-mono hover:underline"
								}, {
									default: P(() => [S(A(e), 1)]),
									_: 2
								}, 1032, ["to"]), t < j(q).length - 1 ? (E(), b("span", Pe, ", ")) : y("", !0)], 64))), 128))])) : y("", !0)])]),
								_: 1
							}),
							C(c, null, {
								header: P(() => [x("h2", Fe, A(j(h)("showroom.catalog.values.title")), 1)]),
								default: P(() => [C(ce, {
									modelValue: j(X),
									"onUpdate:modelValue": t[2] ||= (e) => we(X) ? X.value = e : null,
									attributes: j(G),
									editing: j(J).slot.value,
									error: j(T).error
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
								loading: j(T).busy.value,
								"data-testid": "showroom-product-model-save"
							}, {
								default: P(() => [S(A(j(h)("showroom.catalog.save")), 1)]),
								_: 1
							}, 8, ["loading"])])
						], 32),
						C(ie, {
							assets: j(V).assets ?? [],
							"link-type": "product_model",
							"link-target": j(V).code,
							onChanged: j(B)
						}, null, 8, [
							"assets",
							"link-target",
							"onChanged"
						]),
						C(ae, {
							associations: j(V).associations ?? {},
							quantified: j(V).quantified_associations ?? {},
							"own-associations": j(f)(j(V).associations, j(H)?.associations),
							"own-quantified": j(l)(j(V).quantified_associations, j(H)?.quantified_associations),
							types: j(Ue),
							save: $,
							onChanged: j(B)
						}, null, 8, [
							"associations",
							"quantified",
							"own-associations",
							"own-quantified",
							"types",
							"onChanged"
						]),
						C(se, { values: j(Je) }, null, 8, ["values"]),
						C(c, { "data-testid": j(W) ? "showroom-variant-products" : "showroom-sub-models" }, {
							header: P(() => [x("h2", Ie, A(j(W) ? j(h)("showroom.catalog.product_models.variant_products") : j(h)("showroom.catalog.product_models.sub_models")), 1)]),
							default: P(() => [x("div", Le, [
								j(W) && (j(V).products ?? []).length > 0 ? (E(), b("ul", Re, [(E(!0), b(g, null, k(j(V).products ?? [], (e) => (E(), b("li", { key: e }, [C(n, {
									to: j(s)(e),
									class: "font-mono text-sm hover:underline",
									"data-testid": `showroom-variant-product-${e}`
								}, {
									default: P(() => [S(A(e), 1)]),
									_: 2
								}, 1032, ["to", "data-testid"])]))), 128))])) : y("", !0),
								!j(W) && (j(V).children ?? []).length > 0 ? (E(), b("ul", ze, [(E(!0), b(g, null, k(j(V).children ?? [], (e) => (E(), b("li", { key: e }, [C(n, {
									to: j(ne)(e),
									class: "font-mono text-sm hover:underline",
									"data-testid": `showroom-sub-model-${e}`
								}, {
									default: P(() => [S(A(e), 1)]),
									_: 2
								}, 1032, ["to", "data-testid"])]))), 128))])) : y("", !0),
								C(re, { action: j(M) }, null, 8, ["action"]),
								x("form", {
									class: "flex flex-wrap items-start gap-3",
									"data-testid": "showroom-child-create-form",
									onSubmit: F(Qe, ["prevent"])
								}, [
									C(o, {
										label: j(W) ? j(h)("showroom.catalog.fields.identifier") : j(h)("showroom.catalog.fields.code"),
										error: j(M).error(j(W) ? "identifier" : "code"),
										required: ""
									}, {
										default: P(() => [C(a, {
											modelValue: j(Z).key,
											"onUpdate:modelValue": t[3] ||= (e) => j(Z).key = e,
											required: "",
											class: "w-56",
											"data-testid": "showroom-child-key"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									C(Ee, {
										modelValue: j(Q),
										"onUpdate:modelValue": t[4] ||= (e) => we(Q) ? Q.value = e : null,
										axes: j(K),
										testid: "showroom-child",
										error: j(M).error
									}, null, 8, [
										"modelValue",
										"axes",
										"error"
									]),
									C(r, {
										type: "submit",
										icon: "i-lucide-plus",
										loading: j(M).busy.value,
										class: "mt-field-label",
										"data-testid": "showroom-child-create"
									}, {
										default: P(() => [S(A(j(W) ? j(h)("showroom.catalog.product_models.add_variant") : j(h)("showroom.catalog.product_models.add_sub_model")), 1)]),
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
export { R as default };
