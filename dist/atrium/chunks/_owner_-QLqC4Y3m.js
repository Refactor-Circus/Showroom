import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { t as n } from "./ExternalIds-ATlFXCLK.js";
import { A as r, C as i, D as a, O as o, a as s, c, f as l, j as u, k as d, s as f } from "./catalog-DCoO3A8a.js";
import { u as p } from "./structure-DbqcM-iO.js";
import { t as m } from "./AssetThumbnail-DCu2iAUU.js";
import { i as h } from "./assets-DNUkY-zI.js";
import { n as g } from "./owners-C7wgNkGA.js";
import { AtriumIntegrationScreen as _, navigateTo as v } from "@refactor-circus/atrium";
import { UBadge as ee, UButton as te, UCard as y, UFormField as ne, UIcon as re, UInput as b } from "@refactor-circus/atrium/ui";
import { Fragment as x, computed as S, createBlock as C, createCommentVNode as w, createElementBlock as T, createElementVNode as E, createTextVNode as D, createVNode as O, defineComponent as k, isRef as A, openBlock as j, reactive as M, ref as N, renderList as P, resolveComponent as ie, shallowRef as F, toDisplayString as I, unref as L, useTemplateRef as R, vModelCheckbox as ae, vModelSelect as oe, watch as se, withCtx as z, withDirectives as B, withModifiers as V } from "vue";
import { useI18n as H } from "vue-i18n";
import { useRoute as ce } from "vue-router";
// src/components/owners/OwnerAssets.vue?vue&type=script&setup=true&lang.ts
var U = { class: "font-semibold" }, W = { class: "space-y-4" }, G = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-owner-assets-empty"
}, K = {
	key: 1,
	class: "grid grid-cols-2 gap-3 sm:grid-cols-4"
}, le = /* @__PURE__ */ k({
	__name: "OwnerAssets",
	props: {
		assets: {},
		owner: {}
	},
	emits: ["changed"],
	setup(t, { emit: n }) {
		let r = t, a = n, { t: s } = H(), c = u(), l = o(), d = F(null), f = N("logo"), p = R("fileInput");
		function g(e) {
			d.value = e.target.files?.[0] ?? null;
		}
		function _(e) {
			return [e.role, e.inherited ? s("showroom.catalog.inherited") : e.code].filter((e) => e).join(" · ");
		}
		async function v() {
			let e = d.value;
			c && e && await l.run(async () => {
				let t = new FormData();
				t.append("file", e);
				let n = i(await c.POST("/assets", {
					body: t,
					bodySerializer: (e) => e
				})).data;
				i(await c.POST("/assets/{asset}/links", {
					params: { path: { asset: n.code } },
					body: {
						type: "owner",
						target: r.owner,
						...f.value.trim() ? { role: f.value.trim() } : {}
					}
				}));
			}) && (d.value = null, p.value && (p.value.value = ""), a("changed"));
		}
		return (t, n) => {
			let i = ie("RouterLink"), a = ne, o = b, c = te, u = y;
			return j(), C(u, { "data-testid": "showroom-owner-assets" }, {
				header: z(() => [E("h2", U, I(L(s)("showroom.catalog.assets.title")), 1)]),
				default: z(() => [E("div", W, [
					r.assets.length === 0 ? (j(), T("p", G, I(L(s)("showroom.catalog.assets.none")), 1)) : (j(), T("div", K, [(j(!0), T(x, null, P(r.assets, (e) => (j(), C(i, {
						key: `${e.inherited ? "inherited" : "own"}-${e.code}`,
						to: L(h)(e.code),
						class: "flex flex-col gap-2 rounded-md border border-default p-2 hover:border-primary",
						"data-testid": `showroom-asset-${e.code}`
					}, {
						default: z(() => [O(m, {
							asset: {
								filename: e.code,
								mime_type: e.mime_type,
								url: e.url ?? ""
							},
							caption: _(e)
						}, null, 8, ["asset", "caption"])]),
						_: 2
					}, 1032, ["to", "data-testid"]))), 128))])),
					O(e, { action: L(l) }, null, 8, ["action"]),
					E("form", {
						class: "flex flex-wrap items-end gap-3",
						"data-testid": "showroom-asset-upload",
						onSubmit: V(v, ["prevent"])
					}, [
						O(a, {
							label: L(s)("showroom.catalog.assets.upload_and_link"),
							error: L(l).error("file")
						}, {
							default: z(() => [E("input", {
								ref: "fileInput",
								type: "file",
								required: "",
								class: "text-sm",
								"data-testid": "showroom-asset-file",
								onChange: g
							}, null, 544)]),
							_: 1
						}, 8, ["label", "error"]),
						O(a, {
							label: L(s)("showroom.catalog.assets.role"),
							error: L(l).error("role")
						}, {
							default: z(() => [O(o, {
								modelValue: L(f),
								"onUpdate:modelValue": n[0] ||= (e) => A(f) ? f.value = e : null,
								class: "w-32",
								"data-testid": "showroom-asset-role"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						O(c, {
							type: "submit",
							icon: "i-lucide-upload",
							loading: L(l).busy.value,
							"data-testid": "showroom-asset-upload-submit"
						}, {
							default: z(() => [D(I(L(s)("showroom.catalog.assets.upload")), 1)]),
							_: 1
						}, 8, ["loading"])
					], 32)
				])]),
				_: 1
			});
		};
	}
}), ue = {
	class: "space-y-4",
	"data-testid": "showroom-owner"
}, de = {
	key: 0,
	class: "space-y-5"
}, fe = {
	key: 0,
	class: "flex flex-wrap items-center gap-1 text-sm text-muted",
	"data-testid": "showroom-owner-chain"
}, pe = { class: "font-mono text-default" }, me = { class: "flex flex-wrap items-start justify-between gap-3" }, he = {
	class: "text-xl font-semibold",
	"data-testid": "showroom-owner-title"
}, ge = {
	class: "font-mono text-sm text-muted",
	"data-testid": "showroom-owner-subtitle"
}, _e = { class: "flex flex-wrap items-center gap-2" }, ve = {
	class: "text-sm text-muted",
	"data-testid": "showroom-owner-counts"
}, ye = { class: "font-semibold" }, be = { class: "flex flex-wrap items-end gap-4" }, xe = { class: "flex h-9 items-center gap-2 text-sm" }, Se = { class: "font-semibold" }, Ce = { class: "flex flex-col gap-4" }, we = { class: "text-sm" }, Te = { class: "text-muted" }, Ee = {
	class: "font-mono",
	"data-testid": "showroom-owner-slug"
}, De = {
	key: 0,
	"data-testid": "showroom-own-flags"
}, Oe = { class: "mb-1 text-xs font-medium uppercase text-muted" }, ke = { class: "flex flex-wrap gap-3" }, Ae = [
	"value",
	"checked",
	"data-testid",
	"onChange"
], je = { class: "font-mono text-xs" }, Me = {
	key: 0,
	class: "text-muted"
}, Ne = {
	key: 0,
	class: "text-sm text-muted"
}, Pe = {
	key: 0,
	class: "text-xs text-error"
}, Fe = { "data-testid": "showroom-effective-flags" }, Ie = { class: "mb-1 text-xs font-medium uppercase text-muted" }, Le = { class: "flex flex-wrap gap-3" }, Re = ["data-effective-flag"], ze = {
	key: 0,
	class: "text-xs text-muted"
}, Be = {
	key: 0,
	class: "text-sm text-muted"
}, Ve = { class: "grid gap-5 lg:grid-cols-2" }, He = { class: "font-semibold" }, Ue = { class: "space-y-4" }, We = {
	key: 0,
	class: "text-sm text-muted"
}, Ge = {
	key: 1,
	class: "flex flex-wrap gap-2"
}, Ke = {
	value: "",
	disabled: ""
}, qe = ["value"], Je = {
	key: 2,
	class: "text-xs text-error"
}, q = /* @__PURE__ */ k({
	__name: "[owner]",
	setup(u) {
		let { t: m, locale: h } = H(), C = ce(), k = o(), F = o(), R = S(() => String(C.params.owner)), { client: U, data: W, pending: G, failed: K, status: q, reload: J } = d(async (e) => i(await e.GET("/owners/{owner}", { params: { path: { owner: R.value } } })).data, R), Y = S(() => W.value), Ye = S(() => (Y.value?.chain ?? []).slice(0, -1)), X = r(async (e) => i(await e.GET("/owner-types", { params: { query: { per_page: 100 } } }))), Xe = r(async (e) => i(await e.GET("/flags", { params: { query: { per_page: 100 } } }))), Ze = S(() => Xe.value.filter((e) => e.applies_to.includes("owner"))), Qe = S(() => X.value.filter((e) => e.parent_types === null || e.parent_types === void 0 || e.parent_types.includes(Y.value?.type ?? ""))), Z = M({
			label: "",
			parent: "",
			enabled: !0,
			flags: []
		}), Q = N(c(null)), $ = M({
			code: "",
			type: ""
		});
		se(W, () => {
			let e = Y.value;
			e && (Z.label = e.labels[h.value] ?? "", Z.parent = e.parent ?? "", Z.enabled = e.enabled, Z.flags = [...e.flags ?? []], Q.value = c(e.external_ids));
		}, { immediate: !0 });
		function $e(e, t) {
			Z.flags = t ? [...Z.flags.filter((t) => t !== e), e] : Z.flags.filter((t) => t !== e);
		}
		function et(e) {
			return e.target.checked;
		}
		function tt(e) {
			let t = X.value.find((t) => t.code === e);
			return t ? l(t.labels, h.value, t.code) : e ?? "";
		}
		async function nt() {
			let e = Y.value;
			if (!U || !e) return;
			let t = {
				labels: p(e.labels, h.value, Z.label),
				parent: s(Z.parent),
				enabled: Z.enabled,
				external_ids: f(Q.value),
				...e.flags === void 0 ? {} : { flags: Z.flags }
			};
			await k.run(async () => i(await U.PATCH("/owners/{owner}", {
				params: { path: { owner: R.value } },
				body: a(t)
			}))) && await J();
		}
		async function rt() {
			let e = Y.value;
			if (!U || !e) return;
			let t = {
				code: $.code.trim(),
				type: $.type,
				parent: e.code
			};
			await F.run(async () => i(await U.POST("/owners", { body: a(t) }))) && ($.code = "", await J());
		}
		async function it() {
			U && window.confirm(m("showroom.owners.confirm_delete")) && await k.run(async () => i(await U.DELETE("/owners/{owner}", { params: { path: { owner: R.value } } }))) && await v("/showroom/owners");
		}
		return (r, i) => {
			let a = ie("RouterLink"), o = re, s = ee, c = te, u = b, d = ne, f = y, p = _;
			return j(), T("div", ue, [O(p, {
				"screen-key": "showroom.owner",
				record: L(R)
			}, {
				default: z(() => [O(t, {
					ready: !L(G) && L(Y) !== null,
					failed: L(K),
					status: L(q)
				}, {
					default: z(() => [L(Y) ? (j(), T("div", de, [
						(L(Y).chain ?? []).length > 1 ? (j(), T("nav", fe, [(j(!0), T(x, null, P(L(Ye), (e) => (j(), T(x, { key: e }, [O(a, {
							to: L(g)(e),
							class: "font-mono hover:underline"
						}, {
							default: z(() => [D(I(e), 1)]),
							_: 2
						}, 1032, ["to"]), O(o, {
							name: "i-lucide-chevron-right",
							class: "size-3"
						})], 64))), 128)), E("span", pe, I(L(Y).code), 1)])) : w("", !0),
						E("div", me, [E("div", null, [E("h1", he, I(L(l)(L(Y).labels, L(h), L(Y).code)), 1), E("p", ge, I([L(Y).code, L(Y).type].filter((e) => e).join(" · ")), 1)]), E("div", _e, [
							O(s, {
								color: L(Y).enabled ? "success" : "neutral",
								variant: "subtle",
								"data-testid": "showroom-owner-enabled"
							}, {
								default: z(() => [D(I(L(Y).enabled ? L(m)("showroom.catalog.enabled") : L(m)("showroom.catalog.disabled")), 1)]),
								_: 1
							}, 8, ["color"]),
							O(c, {
								to: `/showroom/products?owner=${encodeURIComponent(L(Y).code)}`,
								variant: "outline",
								icon: "i-lucide-package",
								"data-testid": "showroom-owner-products"
							}, {
								default: z(() => [D(I(L(m)("showroom.owners.products")), 1)]),
								_: 1
							}, 8, ["to"]),
							O(c, {
								color: "error",
								variant: "outline",
								icon: "i-lucide-trash-2",
								loading: L(k).busy.value,
								"data-testid": "showroom-owner-delete",
								onClick: it
							}, {
								default: z(() => [D(I(L(m)("showroom.catalog.delete")), 1)]),
								_: 1
							}, 8, ["loading"])
						])]),
						E("p", ve, I(L(m)("showroom.owners.counts", {
							products: L(Y).products_count ?? 0,
							models: L(Y).product_models_count ?? 0
						})), 1),
						E("form", {
							class: "space-y-5",
							"data-testid": "showroom-owner-form",
							onSubmit: V(nt, ["prevent"])
						}, [
							O(e, { action: L(k) }, null, 8, ["action"]),
							O(f, null, {
								header: z(() => [E("h2", ye, I(L(m)("showroom.owners.details")), 1)]),
								default: z(() => [E("div", be, [
									O(d, {
										label: L(m)("showroom.catalog.fields.label_in", { locale: L(h) }),
										error: L(k).error("labels")
									}, {
										default: z(() => [O(u, {
											modelValue: L(Z).label,
											"onUpdate:modelValue": i[0] ||= (e) => L(Z).label = e,
											class: "w-56",
											"data-testid": "showroom-owner-label"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									O(d, {
										label: L(m)("showroom.catalog.fields.parent"),
										hint: L(m)("showroom.owners.move_hint"),
										error: L(k).error("parent")
									}, {
										default: z(() => [O(u, {
											modelValue: L(Z).parent,
											"onUpdate:modelValue": i[1] ||= (e) => L(Z).parent = e,
											class: "w-48",
											"data-testid": "showroom-owner-parent"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, [
										"label",
										"hint",
										"error"
									]),
									E("label", xe, [B(E("input", {
										"onUpdate:modelValue": i[2] ||= (e) => L(Z).enabled = e,
										type: "checkbox",
										"data-testid": "showroom-owner-enabled-input"
									}, null, 512), [[ae, L(Z).enabled]]), D(" " + I(L(m)("showroom.catalog.enabled")), 1)])
								])]),
								_: 1
							}),
							O(f, { "data-testid": "showroom-owner-identity" }, {
								header: z(() => [E("h2", Se, I(L(m)("showroom.catalog.products.identifiers_and_flags")), 1)]),
								default: z(() => [E("div", Ce, [
									E("p", we, [E("span", Te, I(L(m)("showroom.catalog.fields.slug")) + ":", 1), E("span", Ee, I(L(Y).slug), 1)]),
									O(n, {
										modelValue: L(Q),
										"onUpdate:modelValue": i[3] ||= (e) => A(Q) ? Q.value = e : null,
										"synced-at": L(Y).external_ids_synced_at ?? {},
										error: L(k).error
									}, null, 8, [
										"modelValue",
										"synced-at",
										"error"
									]),
									L(Y).flags === void 0 ? w("", !0) : (j(), T("div", De, [
										E("p", Oe, I(L(m)("showroom.catalog.flags.own")), 1),
										E("div", ke, [(j(!0), T(x, null, P(L(Ze), (e) => (j(), T("label", {
											key: e.code,
											class: "flex items-center gap-1.5 text-sm"
										}, [
											E("input", {
												type: "checkbox",
												value: e.code,
												checked: L(Z).flags.includes(e.code),
												"data-testid": `showroom-flag-${e.code}`,
												onChange: (t) => $e(e.code, et(t))
											}, null, 40, Ae),
											E("span", je, I(e.code), 1),
											L(l)(e.labels, L(h), e.code) === e.code ? w("", !0) : (j(), T("span", Me, I(L(l)(e.labels, L(h), e.code)), 1))
										]))), 128)), L(Ze).length === 0 ? (j(), T("span", Ne, I(L(m)("showroom.owners.no_flags")), 1)) : w("", !0)]),
										L(k).error("flags") ? (j(), T("small", Pe, I(L(k).error("flags")), 1)) : w("", !0)
									])),
									E("div", Fe, [E("p", Ie, I(L(m)("showroom.catalog.flags.effective")), 1), E("ul", Le, [(j(!0), T(x, null, P(L(Y).effective_flags ?? [], (e) => (j(), T("li", {
										key: e,
										class: "flex items-center gap-1.5 text-sm",
										"data-effective-flag": e
									}, [O(s, {
										color: (L(Y).flags ?? []).includes(e) ? "primary" : "neutral",
										variant: "subtle"
									}, {
										default: z(() => [D(I(e), 1)]),
										_: 2
									}, 1032, ["color"]), (L(Y).flags ?? []).includes(e) ? w("", !0) : (j(), T("span", ze, I(L(m)("showroom.catalog.inherited")), 1))], 8, Re))), 128)), (L(Y).effective_flags ?? []).length === 0 ? (j(), T("li", Be, I(L(m)("showroom.catalog.none")), 1)) : w("", !0)])])
								])]),
								_: 1
							}),
							E("div", null, [O(c, {
								type: "submit",
								icon: "i-lucide-check",
								loading: L(k).busy.value,
								"data-testid": "showroom-owner-save"
							}, {
								default: z(() => [D(I(L(m)("showroom.catalog.save")), 1)]),
								_: 1
							}, 8, ["loading"])])
						], 32),
						E("div", Ve, [O(f, { "data-testid": "showroom-owner-children" }, {
							header: z(() => [E("h2", He, I(L(m)("showroom.owners.children")), 1)]),
							default: z(() => [E("div", Ue, [
								(L(Y).children ?? []).length === 0 ? (j(), T("p", We, I(L(m)("showroom.owners.no_children")), 1)) : (j(), T("ul", Ge, [(j(!0), T(x, null, P(L(Y).children, (e) => (j(), T("li", { key: e }, [O(a, {
									to: L(g)(e),
									class: "font-mono text-sm hover:underline",
									"data-testid": `showroom-owner-child-${e}`
								}, {
									default: z(() => [D(I(e), 1)]),
									_: 2
								}, 1032, ["to", "data-testid"])]))), 128))])),
								E("form", {
									class: "flex flex-wrap items-end gap-3",
									"data-testid": "showroom-owner-child-form",
									onSubmit: V(rt, ["prevent"])
								}, [
									O(e, {
										action: L(F),
										class: "w-full"
									}, null, 8, ["action"]),
									O(d, {
										label: L(m)("showroom.catalog.fields.code"),
										error: L(F).error("code"),
										required: ""
									}, {
										default: z(() => [O(u, {
											modelValue: L($).code,
											"onUpdate:modelValue": i[4] ||= (e) => L($).code = e,
											required: "",
											class: "w-40",
											"data-testid": "showroom-owner-child-code"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									O(d, {
										label: L(m)("showroom.catalog.fields.type"),
										error: L(F).error("type"),
										required: ""
									}, {
										default: z(() => [B(E("select", {
											"onUpdate:modelValue": i[5] ||= (e) => L($).type = e,
											required: "",
											class: "w-40 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
											"data-testid": "showroom-owner-child-type"
										}, [E("option", Ke, I(L(m)("showroom.owners.choose_type")), 1), (j(!0), T(x, null, P(L(Qe), (e) => (j(), T("option", {
											key: e.code,
											value: e.code
										}, I(tt(e.code)), 9, qe))), 128))], 512), [[oe, L($).type]])]),
										_: 1
									}, 8, ["label", "error"]),
									O(c, {
										type: "submit",
										icon: "i-lucide-plus",
										loading: L(F).busy.value,
										"data-testid": "showroom-owner-child-submit"
									}, {
										default: z(() => [D(I(L(m)("showroom.owners.add_child")), 1)]),
										_: 1
									}, 8, ["loading"])
								], 32),
								L(F).error("parent") ? (j(), T("small", Je, I(L(F).error("parent")), 1)) : w("", !0)
							])]),
							_: 1
						}), O(le, {
							assets: L(Y).assets ?? [],
							owner: L(Y).code,
							onChanged: L(J)
						}, null, 8, [
							"assets",
							"owner",
							"onChanged"
						])])
					])) : w("", !0)]),
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
export { q as default };
