import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { A as n, C as r, D as i, O as a, f as o, k as s } from "./catalog-DCoO3A8a.js";
import { t as ee } from "./AttributeFlags-B7-82bx1.js";
import { _ as c, f as te, g as l, u, v as d, y as f } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as ne, navigateTo as re } from "@refactor-circus/atrium";
import { UBadge as ie, UButton as ae, UCard as oe, UFormField as se, UInput as ce, UTextarea as le } from "@refactor-circus/atrium/ui";
import { Fragment as p, computed as m, createBlock as ue, createCommentVNode as h, createElementBlock as g, createElementVNode as _, createTextVNode as v, createVNode as y, defineComponent as de, isRef as fe, openBlock as b, reactive as x, ref as S, renderList as C, toDisplayString as w, unref as T, vModelSelect as pe, watch as me, withCtx as E, withDirectives as he, withModifiers as D } from "vue";
import { useI18n as ge } from "vue-i18n";
import { useRoute as O } from "vue-router";
// src/pages/attributes/[attribute].vue?vue&type=script&setup=true&lang.ts
var k = {
	class: "space-y-4",
	"data-testid": "showroom-attribute"
}, A = {
	key: 0,
	class: "space-y-5"
}, j = { class: "flex flex-wrap items-start justify-between gap-3" }, M = {
	class: "text-xl font-semibold",
	"data-testid": "showroom-attribute-title"
}, N = { class: "font-mono text-sm text-muted" }, P = { class: "flex items-center gap-2" }, F = { class: "font-semibold" }, I = { value: "" }, L = ["value"], _e = ["value"], ve = { class: "font-semibold" }, ye = { class: "space-y-4" }, be = {
	key: 0,
	class: "text-sm text-muted"
}, R = {
	key: 1,
	class: "w-full text-left text-sm"
}, xe = { class: "text-muted" }, Se = { class: "py-1 pr-3 font-medium" }, Ce = { class: "py-1 pr-3 font-medium" }, we = { class: "py-1 pr-3 font-medium" }, Te = { class: "divide-y divide-default" }, Ee = ["data-testid"], De = { class: "py-1 pr-3 font-mono text-xs" }, Oe = { class: "py-1 pr-3" }, ke = { class: "py-1 pr-3 tabular-nums" }, Ae = { class: "py-1" }, z = /* @__PURE__ */ de({
	__name: "[attribute]",
	setup(de) {
		let { t: z, locale: B } = ge(), je = O(), V = a(), H = a(), U = m(() => String(je.params.attribute)), { client: W, data: G, pending: Me, failed: Ne, status: Pe, reload: K } = s(async (e) => r(await e.GET("/attributes/{attribute}", { params: { path: { attribute: U.value } } })).data, U), q = n(async (e) => r(await e.GET("/attribute-groups", { params: { query: { per_page: 100 } } }))), J = m(() => G.value), Y = m(() => J.value?.options ?? []), X = x({
			group: "",
			label: "",
			settings: "",
			sort_order: "0"
		}), Z = S({
			is_unique: !1,
			is_localizable: !1,
			is_scopable: !1
		}), Q = x({
			code: "",
			label: "",
			sort_order: "0"
		}), $ = S(null);
		me(G, () => {
			let e = J.value;
			e && (X.group = e.group ?? "", X.label = e.labels[B.value] ?? "", X.settings = d(e.settings), X.sort_order = String(e.sort_order), Z.value = {
				is_unique: e.is_unique,
				is_localizable: e.is_localizable,
				is_scopable: e.is_scopable
			});
		}, { immediate: !0 });
		async function Fe() {
			let e = J.value, t = c(X.settings);
			if ($.value = t === null ? z("showroom.structure.attributes.invalid_settings") : null, !W || !e || t === null) return;
			let n = {
				group: X.group || null,
				labels: u(e.labels, B.value, X.label),
				...Z.value,
				settings: t,
				sort_order: f(X.sort_order) ?? 0
			};
			await V.run(async () => r(await W.PATCH("/attributes/{attribute}", {
				params: { path: { attribute: U.value } },
				body: i(n)
			}))) && await K();
		}
		async function Ie() {
			W && window.confirm(z("showroom.structure.attributes.confirm_delete")) && await V.run(async () => r(await W.DELETE("/attributes/{attribute}", { params: { path: { attribute: U.value } } }))) && await re("/showroom/attributes");
		}
		async function Le() {
			if (!W) return;
			let e = {
				code: Q.code.trim(),
				...l(B.value, Q.label),
				sort_order: f(Q.sort_order) ?? 0
			};
			await H.run(async () => r(await W.POST("/attributes/{attribute}/options", {
				params: { path: { attribute: U.value } },
				body: i(e)
			}))) && (Q.code = "", Q.label = "", Q.sort_order = "0", await K());
		}
		async function Re(e) {
			W && window.confirm(z("showroom.structure.attributes.confirm_delete_option", { option: e })) && await H.run(async () => r(await W.DELETE("/attributes/{attribute}/options/{option}", { params: { path: {
				attribute: U.value,
				option: e
			} } }))) && await K();
		}
		return (n, r) => {
			let i = ie, a = ae, s = se, c = ce, l = le, u = oe, d = ne;
			return b(), g("div", k, [y(d, {
				"screen-key": "showroom.attribute",
				record: T(U)
			}, {
				default: E(() => [y(t, {
					ready: !T(Me) && T(J) !== null,
					failed: T(Ne),
					status: T(Pe)
				}, {
					default: E(() => [T(J) ? (b(), g("div", A, [
						_("div", j, [_("div", null, [_("h1", M, w(T(o)(T(J).labels, T(B), T(J).code)), 1), _("p", N, w(T(J).code), 1)]), _("div", P, [y(i, {
							color: "neutral",
							variant: "subtle",
							"data-testid": "showroom-attribute-type"
						}, {
							default: E(() => [v(w(T(J).type), 1)]),
							_: 1
						}), y(a, {
							color: "error",
							variant: "outline",
							icon: "i-lucide-trash-2",
							loading: T(V).busy.value,
							"data-testid": "showroom-attribute-delete",
							onClick: Ie
						}, {
							default: E(() => [v(w(T(z)("showroom.catalog.delete")), 1)]),
							_: 1
						}, 8, ["loading"])])]),
						y(e, { action: T(V) }, null, 8, ["action"]),
						y(u, null, {
							header: E(() => [_("h2", F, w(T(z)("showroom.structure.details")), 1)]),
							default: E(() => [_("form", {
								class: "flex max-w-xl flex-col gap-4",
								"data-testid": "showroom-attribute-form",
								onSubmit: D(Fe, ["prevent"])
							}, [
								y(s, {
									label: T(z)("showroom.catalog.fields.group"),
									error: T(V).error("group")
								}, {
									default: E(() => [he(_("select", {
										"onUpdate:modelValue": r[0] ||= (e) => T(X).group = e,
										class: "w-64 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
										"data-testid": "showroom-attribute-group"
									}, [
										_("option", I, w(T(z)("showroom.structure.attributes.no_group")), 1),
										T(X).group && !T(q).some((e) => e.code === T(X).group) ? (b(), g("option", {
											key: 0,
											value: T(X).group
										}, w(T(X).group), 9, L)) : h("", !0),
										(b(!0), g(p, null, C(T(q), (e) => (b(), g("option", {
											key: e.code,
											value: e.code
										}, w(T(o)(e.labels, T(B), e.code)), 9, _e))), 128))
									], 512), [[pe, T(X).group]])]),
									_: 1
								}, 8, ["label", "error"]),
								y(s, {
									label: T(z)("showroom.catalog.fields.label_in", { locale: T(B) }),
									error: T(V).error("labels")
								}, {
									default: E(() => [y(c, {
										modelValue: T(X).label,
										"onUpdate:modelValue": r[1] ||= (e) => T(X).label = e,
										class: "w-64",
										"data-testid": "showroom-attribute-label"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								y(ee, {
									modelValue: T(Z),
									"onUpdate:modelValue": r[2] ||= (e) => fe(Z) ? Z.value = e : null,
									testid: "showroom-attribute-flag"
								}, null, 8, ["modelValue"]),
								y(s, {
									label: T(z)("showroom.structure.attributes.settings"),
									hint: T(z)("showroom.structure.attributes.settings_hint"),
									error: T($) ?? T(V).error("settings")
								}, {
									default: E(() => [y(l, {
										modelValue: T(X).settings,
										"onUpdate:modelValue": r[3] ||= (e) => T(X).settings = e,
										rows: 4,
										class: "w-full font-mono",
										"data-testid": "showroom-attribute-settings"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								]),
								y(s, {
									label: T(z)("showroom.catalog.fields.sort_order"),
									error: T(V).error("sort_order")
								}, {
									default: E(() => [y(c, {
										modelValue: T(X).sort_order,
										"onUpdate:modelValue": r[4] ||= (e) => T(X).sort_order = e,
										type: "number",
										min: "0",
										class: "w-32",
										"data-testid": "showroom-attribute-sort"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								_("div", null, [y(a, {
									type: "submit",
									icon: "i-lucide-check",
									loading: T(V).busy.value,
									"data-testid": "showroom-attribute-save"
								}, {
									default: E(() => [v(w(T(z)("showroom.catalog.save")), 1)]),
									_: 1
								}, 8, ["loading"])])
							], 32)]),
							_: 1
						}),
						T(te)(T(J).type) ? (b(), ue(u, {
							key: 0,
							"data-testid": "showroom-attribute-options"
						}, {
							header: E(() => [_("h2", ve, w(T(z)("showroom.structure.attributes.options")), 1)]),
							default: E(() => [_("div", ye, [
								T(Y).length === 0 ? (b(), g("p", be, w(T(z)("showroom.structure.attributes.no_options")), 1)) : (b(), g("table", R, [_("thead", xe, [_("tr", null, [
									_("th", Se, w(T(z)("showroom.catalog.fields.code")), 1),
									_("th", Ce, w(T(z)("showroom.catalog.fields.label")), 1),
									_("th", we, w(T(z)("showroom.catalog.fields.sort_order")), 1),
									r[8] ||= _("th", { class: "py-1 font-medium" }, null, -1)
								])]), _("tbody", Te, [(b(!0), g(p, null, C(T(Y), (e) => (b(), g("tr", {
									key: e.code,
									"data-testid": `showroom-option-row-${e.code}`
								}, [
									_("td", De, w(e.code), 1),
									_("td", Oe, w(T(o)(e.labels, T(B), e.code)), 1),
									_("td", ke, w(e.sort_order), 1),
									_("td", Ae, [y(a, {
										variant: "ghost",
										size: "sm",
										icon: "i-lucide-trash-2",
										"aria-label": T(z)("showroom.catalog.delete"),
										"data-testid": `showroom-option-delete-${e.code}`,
										onClick: (t) => Re(e.code)
									}, null, 8, [
										"aria-label",
										"data-testid",
										"onClick"
									])])
								], 8, Ee))), 128))])])),
								y(e, { action: T(H) }, null, 8, ["action"]),
								_("form", {
									class: "flex flex-wrap items-end gap-3",
									"data-testid": "showroom-option-create-form",
									onSubmit: D(Le, ["prevent"])
								}, [
									y(s, {
										label: T(z)("showroom.catalog.fields.code"),
										error: T(H).error("code"),
										required: ""
									}, {
										default: E(() => [y(c, {
											modelValue: T(Q).code,
											"onUpdate:modelValue": r[5] ||= (e) => T(Q).code = e,
											required: "",
											class: "w-48",
											"data-testid": "showroom-option-create-code"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									y(s, {
										label: T(z)("showroom.catalog.fields.label_in", { locale: T(B) }),
										error: T(H).error("labels")
									}, {
										default: E(() => [y(c, {
											modelValue: T(Q).label,
											"onUpdate:modelValue": r[6] ||= (e) => T(Q).label = e,
											class: "w-56",
											"data-testid": "showroom-option-create-label"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									y(s, {
										label: T(z)("showroom.catalog.fields.sort_order"),
										error: T(H).error("sort_order")
									}, {
										default: E(() => [y(c, {
											modelValue: T(Q).sort_order,
											"onUpdate:modelValue": r[7] ||= (e) => T(Q).sort_order = e,
											type: "number",
											min: "0",
											class: "w-28",
											"data-testid": "showroom-option-create-sort"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									y(a, {
										type: "submit",
										icon: "i-lucide-plus",
										loading: T(H).busy.value,
										"data-testid": "showroom-option-create"
									}, {
										default: E(() => [v(w(T(z)("showroom.structure.attributes.add_option")), 1)]),
										_: 1
									}, 8, ["loading"])
								], 32)
							])]),
							_: 1
						})) : h("", !0)
					])) : h("", !0)]),
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
