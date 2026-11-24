import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { C as n, D as r, O as i, f as a, k as o, o as s } from "./catalog-DCoO3A8a.js";
import { a as c, g as l, m as u, o as ee, p as d, u as te, y as f } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as ne, navigateTo as p } from "@refactor-circus/atrium";
import { UButton as re, UCard as ie, UFormField as ae, UInput as oe } from "@refactor-circus/atrium/ui";
import { Fragment as m, computed as h, createBlock as g, createCommentVNode as _, createElementBlock as v, createElementVNode as y, createTextVNode as b, createVNode as x, defineComponent as S, normalizeClass as C, openBlock as w, reactive as T, renderList as E, resolveComponent as D, toDisplayString as O, unref as k, watch as se, withCtx as A, withModifiers as j } from "vue";
import { useI18n as M } from "vue-i18n";
import { useRoute as ce } from "vue-router";
// src/components/structure/CategoryBranch.vue?vue&type=script&setup=true&lang.ts
var N = ["data-testid"], P = { class: "ml-1 font-mono text-xs text-muted" }, le = /* @__PURE__ */ S({
	__name: "CategoryBranch",
	props: {
		nodes: {},
		nested: { type: Boolean }
	},
	setup(e) {
		let { locale: t } = M();
		return (n, r) => {
			let i = D("RouterLink"), o = D("CategoryBranch", !0);
			return w(), v("ul", { class: C(["flex flex-col gap-1", e.nested ? "ml-5 border-l border-default pl-3" : ""]) }, [(w(!0), v(m, null, E(e.nodes, (e) => (w(), v("li", {
				key: e.category.code,
				"data-testid": `showroom-branch-${e.category.code}`
			}, [
				x(i, {
					to: k(s)(e.category.code),
					class: "text-sm hover:underline"
				}, {
					default: A(() => [b(O(k(a)(e.category.labels, k(t), e.category.code)), 1)]),
					_: 2
				}, 1032, ["to"]),
				y("span", P, O(e.category.code), 1),
				e.children.length > 0 ? (w(), g(o, {
					key: 0,
					nodes: e.children,
					nested: ""
				}, null, 8, ["nodes"])) : _("", !0)
			], 8, N))), 128))], 2);
		};
	}
}), F = {
	class: "space-y-4",
	"data-testid": "showroom-category"
}, I = {
	key: 0,
	class: "space-y-5"
}, L = { class: "flex flex-wrap items-start justify-between gap-3" }, R = {
	class: "text-xl font-semibold",
	"data-testid": "showroom-category-title"
}, z = { class: "font-mono text-sm text-muted" }, B = { class: "flex items-center gap-2" }, V = ["aria-label"], H = { class: "font-medium" }, U = { class: "font-semibold" }, ue = { class: "space-y-4" }, de = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-branch-empty"
}, fe = { class: "font-semibold" }, pe = {
	class: "text-sm text-muted",
	"data-testid": "showroom-category-counts"
}, W = /* @__PURE__ */ S({
	__name: "[category]",
	setup(S) {
		let { t: C, locale: N } = M(), P = ce(), W = i(), G = i(), K = h(() => String(P.params.category)), { client: q, data: J, pending: me, failed: he, status: ge, reload: Y } = o(async (e) => {
			let t = n(await e.GET("/categories/{category}", { params: { path: { category: K.value } } })).data, [r, i] = await Promise.all([u(e, t), d(e, t.code)]);
			return {
				category: t,
				chain: r,
				branch: i
			};
		}, K), X = h(() => J.value?.category ?? null), _e = h(() => J.value?.chain.slice(0, -1) ?? []), Z = h(() => c(K.value, J.value?.branch ?? [])), Q = T({
			label: "",
			sort_order: "0",
			parent: ""
		}), $ = T({
			code: "",
			label: "",
			sort_order: "0"
		});
		se(J, () => {
			let e = X.value;
			e && (Q.label = e.labels[N.value] ?? "", Q.sort_order = String(e.sort_order), Q.parent = e.parent ?? "");
		}, { immediate: !0 });
		async function ve() {
			let e = X.value;
			if (!q || !e) return;
			let t = {
				labels: te(e.labels, N.value, Q.label),
				sort_order: f(Q.sort_order) ?? 0,
				parent: Q.parent.trim() || null
			};
			await W.run(async () => n(await q.PATCH("/categories/{category}", {
				params: { path: { category: K.value } },
				body: r(t)
			}))) && await Y();
		}
		async function ye() {
			if (!q) return;
			let e = {
				code: $.code.trim(),
				parent: K.value,
				...l(N.value, $.label),
				sort_order: f($.sort_order) ?? 0
			};
			await G.run(async () => n(await q.POST("/categories", { body: r(e) }))) && ($.code = "", $.label = "", $.sort_order = "0", await Y());
		}
		async function be() {
			q && window.confirm(C("showroom.structure.categories.confirm_delete")) && await W.run(async () => n(await q.DELETE("/categories/{category}", { params: { path: { category: K.value } } }))) && await p("/showroom/categories");
		}
		return (n, r) => {
			let i = re, o = D("RouterLink"), c = oe, l = ae, u = ie, d = ne;
			return w(), v("div", F, [x(d, {
				"screen-key": "showroom.category",
				record: k(K)
			}, {
				default: A(() => [x(t, {
					ready: !k(me) && k(X) !== null,
					failed: k(he),
					status: k(ge)
				}, {
					default: A(() => [k(X) ? (w(), v("div", I, [
						y("div", L, [y("div", null, [y("h1", R, O(k(a)(k(X).labels, k(N), k(X).code)), 1), y("p", z, O(k(X).code), 1)]), y("div", B, [x(i, {
							to: k(ee)(k(X).code),
							variant: "outline",
							icon: "i-lucide-box",
							"data-testid": "showroom-category-products"
						}, {
							default: A(() => [b(O(k(C)("showroom.structure.categories.products")), 1)]),
							_: 1
						}, 8, ["to"]), x(i, {
							color: "error",
							variant: "outline",
							icon: "i-lucide-trash-2",
							loading: k(W).busy.value,
							"data-testid": "showroom-category-delete",
							onClick: be
						}, {
							default: A(() => [b(O(k(C)("showroom.catalog.delete")), 1)]),
							_: 1
						}, 8, ["loading"])])]),
						y("nav", {
							"aria-label": k(C)("showroom.structure.categories.chain"),
							class: "flex flex-wrap items-center gap-1 text-sm",
							"data-testid": "showroom-category-chain"
						}, [
							x(o, {
								to: "/showroom/categories",
								class: "hover:underline"
							}, {
								default: A(() => [b(O(k(C)("showroom.structure.categories.title")), 1)]),
								_: 1
							}),
							(w(!0), v(m, null, E(k(_e), (e) => (w(), v(m, { key: e.code }, [r[6] ||= y("span", { class: "text-muted" }, "›", -1), x(o, {
								to: k(s)(e.code),
								class: "hover:underline"
							}, {
								default: A(() => [b(O(k(a)(e.labels, k(N), e.code)), 1)]),
								_: 2
							}, 1032, ["to"])], 64))), 128)),
							r[7] ||= y("span", { class: "text-muted" }, "›", -1),
							y("span", H, O(k(a)(k(X).labels, k(N), k(X).code)), 1)
						], 8, V),
						x(e, { action: k(W) }, null, 8, ["action"]),
						x(u, { "data-testid": "showroom-category-branch" }, {
							header: A(() => [y("h2", U, O(k(C)("showroom.structure.categories.branch")), 1)]),
							default: A(() => [y("div", ue, [
								k(Z).length === 0 ? (w(), v("p", de, O(k(C)("showroom.structure.categories.no_branch")), 1)) : (w(), g(le, {
									key: 1,
									nodes: k(Z)
								}, null, 8, ["nodes"])),
								x(e, { action: k(G) }, null, 8, ["action"]),
								y("form", {
									class: "flex flex-wrap items-end gap-3",
									"data-testid": "showroom-child-create-form",
									onSubmit: j(ye, ["prevent"])
								}, [
									x(l, {
										label: k(C)("showroom.catalog.fields.code"),
										error: k(G).error("code"),
										required: ""
									}, {
										default: A(() => [x(c, {
											modelValue: k($).code,
											"onUpdate:modelValue": r[0] ||= (e) => k($).code = e,
											required: "",
											class: "w-48",
											"data-testid": "showroom-child-create-code"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									x(l, {
										label: k(C)("showroom.catalog.fields.label_in", { locale: k(N) }),
										error: k(G).error("labels")
									}, {
										default: A(() => [x(c, {
											modelValue: k($).label,
											"onUpdate:modelValue": r[1] ||= (e) => k($).label = e,
											class: "w-56",
											"data-testid": "showroom-child-create-label"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									x(l, {
										label: k(C)("showroom.catalog.fields.sort_order"),
										error: k(G).error("sort_order")
									}, {
										default: A(() => [x(c, {
											modelValue: k($).sort_order,
											"onUpdate:modelValue": r[2] ||= (e) => k($).sort_order = e,
											type: "number",
											min: "0",
											class: "w-28",
											"data-testid": "showroom-child-create-sort"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									x(i, {
										type: "submit",
										icon: "i-lucide-plus",
										loading: k(G).busy.value,
										"data-testid": "showroom-child-create"
									}, {
										default: A(() => [b(O(k(C)("showroom.structure.categories.add_child")), 1)]),
										_: 1
									}, 8, ["loading"])
								], 32)
							])]),
							_: 1
						}),
						x(u, null, {
							header: A(() => [y("h2", fe, O(k(C)("showroom.structure.details")), 1)]),
							default: A(() => [y("form", {
								class: "flex flex-wrap items-end gap-3",
								"data-testid": "showroom-category-form",
								onSubmit: j(ve, ["prevent"])
							}, [
								x(l, {
									label: k(C)("showroom.catalog.fields.label_in", { locale: k(N) }),
									error: k(W).error("labels")
								}, {
									default: A(() => [x(c, {
										modelValue: k(Q).label,
										"onUpdate:modelValue": r[3] ||= (e) => k(Q).label = e,
										class: "w-64",
										"data-testid": "showroom-category-label"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								x(l, {
									label: k(C)("showroom.catalog.fields.sort_order"),
									error: k(W).error("sort_order")
								}, {
									default: A(() => [x(c, {
										modelValue: k(Q).sort_order,
										"onUpdate:modelValue": r[4] ||= (e) => k(Q).sort_order = e,
										type: "number",
										min: "0",
										class: "w-28",
										"data-testid": "showroom-category-sort"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								x(l, {
									label: k(C)("showroom.catalog.fields.parent"),
									hint: k(C)("showroom.structure.categories.move_hint"),
									error: k(W).error("parent")
								}, {
									default: A(() => [x(c, {
										modelValue: k(Q).parent,
										"onUpdate:modelValue": r[5] ||= (e) => k(Q).parent = e,
										class: "w-56",
										"data-testid": "showroom-category-parent"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								]),
								x(i, {
									type: "submit",
									icon: "i-lucide-check",
									loading: k(W).busy.value,
									"data-testid": "showroom-category-save"
								}, {
									default: A(() => [b(O(k(C)("showroom.catalog.save")), 1)]),
									_: 1
								}, 8, ["loading"])
							], 32)]),
							_: 1
						}),
						y("p", pe, O(k(C)("showroom.structure.categories.filed_counts", {
							products: k(X).products_count ?? 0,
							models: k(X).product_models_count ?? 0
						})), 1)
					])) : _("", !0)]),
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
export { W as default };
