import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { C as n, D as r, O as i, f as a, k as o, o as s } from "./catalog-DCoO3A8a.js";
import { a as c, g as l, m as u, o as d, p as f, u as p, y as m } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as ee, AtriumPageHeader as h, AtriumState as g, navigateTo as _ } from "@refactor-circus/atrium";
import { UBreadcrumb as v, UButton as te, UCard as ne, UFormField as y, UInput as re, ULink as ie } from "@refactor-circus/atrium/ui";
import { Fragment as b, computed as x, createBlock as S, createCommentVNode as C, createElementBlock as w, createElementVNode as T, createTextVNode as E, createVNode as D, defineComponent as O, normalizeClass as k, openBlock as A, reactive as j, renderList as M, resolveComponent as N, toDisplayString as P, unref as F, watch as ae, withCtx as I, withModifiers as L } from "vue";
import { useI18n as R } from "vue-i18n";
import { useRoute as z } from "vue-router";
// src/components/structure/CategoryBranch.vue?vue&type=script&setup=true&lang.ts
var B = ["data-testid"], V = { class: "ml-1 font-mono text-xs text-muted" }, H = /* @__PURE__ */ O({
	__name: "CategoryBranch",
	props: {
		nodes: {},
		nested: { type: Boolean }
	},
	setup(e) {
		let { locale: t } = R();
		return (n, r) => {
			let i = ie, o = N("CategoryBranch", !0);
			return A(), w("ul", { class: k(["flex flex-col gap-1", e.nested ? "ml-5 border-l border-default pl-3" : ""]) }, [(A(!0), w(b, null, M(e.nodes, (e) => (A(), w("li", {
				key: e.category.code,
				"data-testid": `showroom-branch-${e.category.code}`
			}, [
				D(i, {
					to: F(s)(e.category.code),
					class: "text-sm hover:underline"
				}, {
					default: I(() => [E(P(F(a)(e.category.labels, F(t), e.category.code)), 1)]),
					_: 2
				}, 1032, ["to"]),
				T("span", V, P(e.category.code), 1),
				e.children.length > 0 ? (A(), S(o, {
					key: 0,
					nodes: e.children,
					nested: ""
				}, null, 8, ["nodes"])) : C("", !0)
			], 8, B))), 128))], 2);
		};
	}
}), U = {
	class: "space-y-4",
	"data-testid": "showroom-category"
}, W = {
	key: 0,
	class: "space-y-5"
}, G = { class: "font-semibold" }, K = { class: "space-y-4" }, q = { class: "font-semibold" }, oe = {
	class: "text-sm text-muted",
	"data-testid": "showroom-category-counts"
}, J = /* @__PURE__ */ O({
	__name: "[category]",
	setup(ie) {
		let { t: b, locale: O } = R(), k = z(), M = i(), N = i(), B = x(() => String(k.params.category)), { client: V, data: J, pending: se, failed: ce, status: le, reload: Y } = o(async (e) => {
			let t = n(await e.GET("/categories/{category}", { params: { path: { category: B.value } } })).data, [r, i] = await Promise.all([u(e, t), f(e, t.code)]);
			return {
				category: t,
				chain: r,
				branch: i
			};
		}, B), X = x(() => J.value?.category ?? null), ue = x(() => J.value?.chain.slice(0, -1) ?? []), Z = x(() => c(B.value, J.value?.branch ?? [])), Q = j({
			label: "",
			sort_order: "0",
			parent: ""
		}), $ = j({
			code: "",
			label: "",
			sort_order: "0"
		});
		ae(J, () => {
			let e = X.value;
			e && (Q.label = e.labels[O.value] ?? "", Q.sort_order = String(e.sort_order), Q.parent = e.parent ?? "");
		}, { immediate: !0 });
		async function de() {
			let e = X.value;
			if (!V || !e) return;
			let t = {
				labels: p(e.labels, O.value, Q.label),
				sort_order: m(Q.sort_order) ?? 0,
				parent: Q.parent.trim() || null
			};
			await M.run(async () => n(await V.PATCH("/categories/{category}", {
				params: { path: { category: B.value } },
				body: r(t)
			}))) && await Y();
		}
		async function fe() {
			if (!V) return;
			let e = {
				code: $.code.trim(),
				parent: B.value,
				...l(O.value, $.label),
				sort_order: m($.sort_order) ?? 0
			};
			await N.run(async () => n(await V.POST("/categories", { body: r(e) }))) && ($.code = "", $.label = "", $.sort_order = "0", await Y());
		}
		async function pe() {
			V && window.confirm(b("showroom.structure.categories.confirm_delete")) && await M.run(async () => n(await V.DELETE("/categories/{category}", { params: { path: { category: B.value } } }))) && await _("/showroom/categories");
		}
		let me = x(() => X.value === null ? [] : [
			{
				label: b("showroom.structure.categories.title"),
				to: "/showroom/categories"
			},
			...ue.value.map((e) => ({
				label: a(e.labels, O.value, e.code),
				to: s(e.code)
			})),
			{ label: a(X.value.labels, O.value, X.value.code) }
		]);
		return (n, r) => {
			let i = te, o = h, s = v, c = g, l = re, u = y, f = ne, p = ee;
			return A(), w("div", U, [D(p, {
				"screen-key": "showroom.category",
				record: F(B)
			}, {
				default: I(() => [D(t, {
					ready: !F(se) && F(X) !== null,
					failed: F(ce),
					status: F(le)
				}, {
					default: I(() => [F(X) ? (A(), w("div", W, [
						D(o, {
							title: F(a)(F(X).labels, F(O), F(X).code),
							description: F(X).code,
							"data-testid": "showroom-category-title"
						}, {
							default: I(() => [D(i, {
								to: F(d)(F(X).code),
								variant: "outline",
								icon: "i-lucide-box",
								"data-testid": "showroom-category-products"
							}, {
								default: I(() => [E(P(F(b)("showroom.structure.categories.products")), 1)]),
								_: 1
							}, 8, ["to"]), D(i, {
								color: "error",
								variant: "outline",
								icon: "i-lucide-trash-2",
								loading: F(M).busy.value,
								"data-testid": "showroom-category-delete",
								onClick: pe
							}, {
								default: I(() => [E(P(F(b)("showroom.catalog.delete")), 1)]),
								_: 1
							}, 8, ["loading"])]),
							_: 1
						}, 8, ["title", "description"]),
						D(s, {
							items: F(me),
							"aria-label": F(b)("showroom.structure.categories.chain"),
							"data-testid": "showroom-category-chain"
						}, null, 8, ["items", "aria-label"]),
						D(e, { action: F(M) }, null, 8, ["action"]),
						D(f, { "data-testid": "showroom-category-branch" }, {
							header: I(() => [T("h2", G, P(F(b)("showroom.structure.categories.branch")), 1)]),
							default: I(() => [T("div", K, [
								F(Z).length === 0 ? (A(), S(c, {
									key: 0,
									compact: "",
									title: F(b)("showroom.structure.categories.no_branch"),
									"data-testid": "showroom-branch-empty"
								}, null, 8, ["title"])) : (A(), S(H, {
									key: 1,
									nodes: F(Z)
								}, null, 8, ["nodes"])),
								D(e, { action: F(N) }, null, 8, ["action"]),
								T("form", {
									class: "flex flex-wrap items-start gap-3",
									"data-testid": "showroom-child-create-form",
									onSubmit: L(fe, ["prevent"])
								}, [
									D(u, {
										label: F(b)("showroom.catalog.fields.code"),
										error: F(N).error("code"),
										required: ""
									}, {
										default: I(() => [D(l, {
											modelValue: F($).code,
											"onUpdate:modelValue": r[0] ||= (e) => F($).code = e,
											required: "",
											class: "w-48",
											"data-testid": "showroom-child-create-code"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									D(u, {
										label: F(b)("showroom.catalog.fields.label_in", { locale: F(O) }),
										error: F(N).error("labels")
									}, {
										default: I(() => [D(l, {
											modelValue: F($).label,
											"onUpdate:modelValue": r[1] ||= (e) => F($).label = e,
											class: "w-56",
											"data-testid": "showroom-child-create-label"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									D(u, {
										label: F(b)("showroom.catalog.fields.sort_order"),
										error: F(N).error("sort_order")
									}, {
										default: I(() => [D(l, {
											modelValue: F($).sort_order,
											"onUpdate:modelValue": r[2] ||= (e) => F($).sort_order = e,
											type: "number",
											min: "0",
											class: "w-28",
											"data-testid": "showroom-child-create-sort"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									D(i, {
										type: "submit",
										icon: "i-lucide-plus",
										loading: F(N).busy.value,
										class: "mt-field-label",
										"data-testid": "showroom-child-create"
									}, {
										default: I(() => [E(P(F(b)("showroom.structure.categories.add_child")), 1)]),
										_: 1
									}, 8, ["loading"])
								], 32)
							])]),
							_: 1
						}),
						D(f, null, {
							header: I(() => [T("h2", q, P(F(b)("showroom.structure.details")), 1)]),
							default: I(() => [T("form", {
								class: "flex flex-wrap items-start gap-3",
								"data-testid": "showroom-category-form",
								onSubmit: L(de, ["prevent"])
							}, [
								D(u, {
									label: F(b)("showroom.catalog.fields.label_in", { locale: F(O) }),
									error: F(M).error("labels")
								}, {
									default: I(() => [D(l, {
										modelValue: F(Q).label,
										"onUpdate:modelValue": r[3] ||= (e) => F(Q).label = e,
										class: "w-64",
										"data-testid": "showroom-category-label"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								D(u, {
									label: F(b)("showroom.catalog.fields.sort_order"),
									error: F(M).error("sort_order")
								}, {
									default: I(() => [D(l, {
										modelValue: F(Q).sort_order,
										"onUpdate:modelValue": r[4] ||= (e) => F(Q).sort_order = e,
										type: "number",
										min: "0",
										class: "w-28",
										"data-testid": "showroom-category-sort"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								D(u, {
									label: F(b)("showroom.catalog.fields.parent"),
									hint: F(b)("showroom.structure.categories.move_hint"),
									error: F(M).error("parent")
								}, {
									default: I(() => [D(l, {
										modelValue: F(Q).parent,
										"onUpdate:modelValue": r[5] ||= (e) => F(Q).parent = e,
										class: "w-56",
										"data-testid": "showroom-category-parent"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								]),
								D(i, {
									type: "submit",
									icon: "i-lucide-check",
									loading: F(M).busy.value,
									class: "mt-field-label",
									"data-testid": "showroom-category-save"
								}, {
									default: I(() => [E(P(F(b)("showroom.catalog.save")), 1)]),
									_: 1
								}, 8, ["loading"])
							], 32)]),
							_: 1
						}),
						T("p", oe, P(F(b)("showroom.structure.categories.filed_counts", {
							products: F(X).products_count ?? 0,
							models: F(X).product_models_count ?? 0
						})), 1)
					])) : C("", !0)]),
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
export { J as default };
